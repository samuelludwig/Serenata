<?php

namespace Serenata\Indexing\Visiting;

use DomainException;
use SplObjectStorage;

use Ds\Stack;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Analysis\Typing\TypeAnalyzer;
use Serenata\Analysis\Typing\TypeResolvingDocblockTypeTransformer;

use Serenata\Common\Range;
use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\DocblockTypeParser\VoidDocblockType;
use Serenata\DocblockTypeParser\MixedDocblockType;
use Serenata\DocblockTypeParser\StringDocblockType;
use Serenata\DocblockTypeParser\DocblockTypeParserInterface;
use Serenata\DocblockTypeParser\SpecializedArrayDocblockType;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use Serenata\Indexing\Structures\Classlike;
use Serenata\Indexing\Structures\AccessModifierNameValue;

use Serenata\Parsing\DocblockParser;

use Serenata\Utility\NodeHelpers;
use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Visitor that traverses a set of nodes, indexing classlikes in the process.
 */
final class ClasslikeIndexingVisitor extends NodeVisitorAbstract
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * @var TypeResolvingDocblockTypeTransformer
     */
    private $typeResolvingDocblockTypeTransformer;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var array|null
     */
    private $accessModifierMap;

    /**
     * @var Structures\File
     */
    private $file;

    /**
     * @var TextDocumentItem
     */
    private $textDocumentItem;

    /**
     * @var Stack Stack<Structures\Classlike>
     */
    private $classlikeStack;

    /**
     * @var array<string,bool>
     */
    private $traitsUsed = [];

    /**
     * Stores classlikes that were found during the traversal.
     *
     * Whilst traversing, classlikes found first may be referencing classlikes found later and the other way around.
     * Because changes are not flushed during traversal, fetching these classlikes may not work if they are located
     * in the same file.
     *
     * We could also flush the changes constantly, but this hurts performance and not fetching information we already
     * have benefits performance in large files with many interdependencies.
     *
     * @var SplObjectStorage
     */
    private $classlikesFound;

    /**
     * @var SplObjectStorage
     */
    private $relationsStorage;

    /**
     * @var SplObjectStorage
     */
    private $traitUseStorage;

    /**
     * @param StorageInterface                           $storage
     * @param TypeAnalyzer                               $typeAnalyzer
     * @param DocblockParser                             $docblockParser
     * @param DocblockTypeParserInterface                $docblockTypeParser
     * @param TypeResolvingDocblockTypeTransformer       $typeResolvingDocblockTypeTransformer
     * @param NodeTypeDeducerInterface                   $nodeTypeDeducer
     * @param Structures\File                            $file
     * @param TextDocumentItem                           $textDocumentItem
     */
    public function __construct(
        StorageInterface $storage,
        TypeAnalyzer $typeAnalyzer,
        DocblockParser $docblockParser,
        DocblockTypeParserInterface $docblockTypeParser,
        TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        Structures\File $file,
        TextDocumentItem $textDocumentItem
    ) {
        $this->storage = $storage;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockParser = $docblockParser;
        $this->docblockTypeParser = $docblockTypeParser;
        $this->typeResolvingDocblockTypeTransformer = $typeResolvingDocblockTypeTransformer;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->file = $file;
        $this->textDocumentItem = $textDocumentItem;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if ($node instanceof Node\Stmt\Property) {
            $this->parseClassPropertyNode($node);
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $this->parseClassMethodNode($node);
        } elseif ($node instanceof Node\Stmt\ClassConst) {
            $this->parseClassConstantStatementNode($node);
        } elseif ($node instanceof Node\Stmt\Class_) {
            $this->processEnterClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $this->processEnterClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->processEnterClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\TraitUse) {
            $this->parseTraitUseNode($node);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
        $value = parent::leaveNode($node);

        if ($node instanceof Node\Stmt\Class_) {
            $this->processLeaveClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $this->processLeaveClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->processLeaveClasslikeNode($node);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function beforeTraverse(array $nodes)
    {
        $this->classlikeStack = new Stack();
        $this->classlikesFound = new SplObjectStorage();
        $this->relationsStorage = new SplObjectStorage();
        $this->traitUseStorage = new SplObjectStorage();

        foreach ($this->file->getClasslikes() as $classlike) {
            $this->file->removeClasslike($classlike);

            $this->storage->delete($classlike);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function afterTraverse(array $nodes)
    {
        // Index relations after traversal as, in PHP, a child class can be defined before a parent class in a single
        // file. When walking the tree and indexing the child, the parent may not yet have been indexed.
        foreach ($this->relationsStorage as $classlike) {
            $node = $this->relationsStorage[$classlike];

            $this->processClassLikeRelations($node, $classlike);

            $this->storage->persist($classlike);
        }

        foreach ($this->traitUseStorage as $classlike) {
            $nodes = $this->traitUseStorage[$classlike];

            foreach ($nodes as $node) {
                $this->processTraitUseNode($node, $classlike);
            }

            $this->storage->persist($classlike);
        }

        return null;
    }

    /**
     * @param Node\Stmt\ClassLike $node
     *
     * @return void
     */
    private function processEnterClasslikeNode(Node\Stmt\ClassLike $node): void
    {
        $this->classlikeStack->push($this->parseClasslikeNode($node));
    }

    /**
     * @param Node\Stmt\ClassLike $node
     *
     * @return void
     */
    private function processLeaveClasslikeNode(Node\Stmt\ClassLike $node): void
    {
        $this->classlikeStack->pop();
    }

    /**
     * @param Node\Stmt\ClassLike $node
     *
     * @return Structures\Classlike
     */
    private function parseClasslikeNode(Node\Stmt\ClassLike $node): Structures\Classlike
    {
        if (!isset($node->namespacedName)) {
            // return;
        }

        $this->traitsUsed = [];

        $docComment = $node->getDocComment() !== null ? $node->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::DEPRECATED,
            DocblockParser::ANNOTATION,
            DocblockParser::DESCRIPTION,
            DocblockParser::METHOD,
            DocblockParser::PROPERTY,
            DocblockParser::PROPERTY_READ,
            DocblockParser::PROPERTY_WRITE,
        ], '');

        $classlike = null;

        $range = new Range(
            Position::createFromByteOffset(
                $node->getAttribute('startFilePos'),
                $this->textDocumentItem->getText(),
                PositionEncoding::VALUE
            ),
            Position::createFromByteOffset(
                $node->getAttribute('endFilePos') + 1,
                $this->textDocumentItem->getText(),
                PositionEncoding::VALUE
            )
        );

        if ($node instanceof Node\Stmt\Class_) {
            $fqcn = null;
            $classlikeName = null;

            if ($node->isAnonymous()) {
                $fqcn = NodeHelpers::getFqcnForAnonymousClassNode($node, $this->textDocumentItem->getUri());
                $classlikeName = mb_substr($fqcn, 1);
            } else {
                $fqcn = '\\' . $node->namespacedName->toString();
                $classlikeName = $node->name->name;
            }

            $classlike = new Structures\Class_(
                $classlikeName,
                $fqcn,
                $this->file,
                $range,
                ((bool) $documentation['descriptions']['short']) ? $documentation['descriptions']['short'] : null,
                ((bool) $documentation['descriptions']['long']) ? $documentation['descriptions']['long'] : null,
                $node->isAnonymous(),
                $node->isAbstract(),
                $node->isFinal(),
                $documentation['annotation'],
                $documentation['deprecated'],
                $docComment !== '' && $docComment !== null,
                null
            );
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $classlike = new Structures\Interface_(
                $node->name->name,
                '\\' . $node->namespacedName->toString(),
                $this->file,
                $range,
                ((bool) $documentation['descriptions']['short']) ? $documentation['descriptions']['short'] : null,
                ((bool) $documentation['descriptions']['long']) ? $documentation['descriptions']['long'] : null,
                $documentation['deprecated'],
                $docComment !== '' && $docComment !== null
            );
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $classlike = new Structures\Trait_(
                $node->name->name,
                '\\' . $node->namespacedName->toString(),
                $this->file,
                $range,
                ((bool) $documentation['descriptions']['short']) ? $documentation['descriptions']['short'] : null,
                ((bool) $documentation['descriptions']['long']) ? $documentation['descriptions']['long'] : null,
                $documentation['deprecated'],
                $docComment !== '' && $docComment !== null
            );
        } else {
            throw new DomainException('Uknown classlike node type "' . get_class($node) . '" encountered');
        }

        $this->storage->persist($classlike);

        $this->classlikesFound->attach($classlike, $classlike->getFqcn());

        $accessModifierMap = $this->getAccessModifierMap();

        $this->relationsStorage->attach($classlike, $node);

        // Index magic properties.
        $magicProperties = array_merge(
            $documentation['properties'],
            $documentation['propertiesReadOnly'],
            $documentation['propertiesWriteOnly']
        );

        $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

        foreach ($magicProperties as $propertyName => $propertyData) {
            // Use the same line as the class definition, it matters for e.g. type resolution.
            $propertyData['name'] = mb_substr($propertyName, 1);

            $this->indexMagicProperty(
                $propertyData,
                $classlike,
                $accessModifierMap[AccessModifierNameValue::PUBLIC_],
                $filePosition
            );
        }

        // Index magic methods.
        foreach ($documentation['methods'] as $methodName => $methodData) {
            // Use the same line as the class definition, it matters for e.g. type resolution.
            $methodData['name'] = $methodName;

            $this->indexMagicMethod(
                $methodData,
                $classlike,
                $accessModifierMap[AccessModifierNameValue::PUBLIC_],
                $filePosition
            );
        }

        $this->indexClassKeyword($classlike);

        return $classlike;
    }

    /**
     * @param Node\Stmt\TraitUse $node
     *
     * @return void
     */
    private function parseTraitUseNode(Node\Stmt\TraitUse $node): void
    {
        $traitUses = [];

        if ($this->traitUseStorage->contains($this->classlikeStack->peek())) {
            $traitUses = $this->traitUseStorage[$this->classlikeStack->peek()];
        }

        $traitUses[] = $node;

        $this->traitUseStorage->attach($this->classlikeStack->peek(), $traitUses);
    }

    /**
     * @param Node\Stmt\ClassLike  $node
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function processClassLikeRelations(Node\Stmt\ClassLike $node, Structures\Classlike $classlike): void
    {
        if ($classlike instanceof Structures\Class_) {
            assert($node instanceof Node\Stmt\Class_);

            $this->processClassRelations($node, $classlike);
        } elseif ($classlike instanceof Structures\Interface_) {
            assert($node instanceof Node\Stmt\Interface_);

            $this->processInterfaceRelations($node, $classlike);
        } elseif ($classlike instanceof Structures\Trait_) {
            // Traits can't have relations.
        } else {
            throw new DomainException("Don't know how to handle classlike of type " . get_class($classlike));
        }
    }

    /**
     * @param Node\Stmt\Class_  $node
     * @param Structures\Class_ $class
     *
     * @return void
     */
    private function processClassRelations(Node\Stmt\Class_ $node, Structures\Class_ $class): void
    {
        if ($node->extends !== null) {
            $parent = NodeHelpers::fetchClassName($node->extends->getAttribute('resolvedName'));

            $parentFqcn = $this->typeAnalyzer->getNormalizedFqcn($parent);

            $linkEntity = $this->findStructureByFqcn($parentFqcn);

            if ($linkEntity !== null && $linkEntity instanceof Structures\Class_) {
                $class->setParent($linkEntity);
            } else {
                $class->setParentFqcn($parentFqcn);
            }
        }

        $implementedFqcns = array_unique(array_map(function (Node\Name $name): string {
            $resolvedName = NodeHelpers::fetchClassName($name->getAttribute('resolvedName'));

            return $this->typeAnalyzer->getNormalizedFqcn($resolvedName);
        }, $node->implements));

        foreach ($implementedFqcns as $implementedFqcn) {
            $linkEntity = $this->findStructureByFqcn($implementedFqcn);

            if ($linkEntity !== null && $linkEntity instanceof Structures\Interface_) {
                $class->addInterface($linkEntity);
            } else {
                $class->addInterfaceFqcn($implementedFqcn);
            }
        }
    }

    /**
     * @param Node\Stmt\Interface_  $node
     * @param Structures\Interface_ $interface
     *
     * @return void
     */
    private function processInterfaceRelations(Node\Stmt\Interface_ $node, Structures\Interface_ $interface): void
    {
        $extendedFqcns = array_unique(array_map(function (Node\Name $name): string {
            $resolvedName = NodeHelpers::fetchClassName($name->getAttribute('resolvedName'));

            return $this->typeAnalyzer->getNormalizedFqcn($resolvedName);
        }, $node->extends));

        foreach ($extendedFqcns as $extendedFqcn) {
            $linkEntity = $this->findStructureByFqcn($extendedFqcn);

            if ($linkEntity !== null && $linkEntity instanceof Structures\Interface_) {
                $interface->addParent($linkEntity);
            } else {
                $interface->addParentFqcn($extendedFqcn);
            }
        }
    }

    /**
     * @param Node\Stmt\TraitUse   $node
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function processTraitUseNode(Node\Stmt\TraitUse $node, Structures\Classlike $classlike): void
    {
        if (!$classlike instanceof Structures\Class_ && !$classlike instanceof Structures\Trait_) {
            return; // Nope, interfaces can't use traits.
        }

        foreach ($node->traits as $traitName) {
            $traitFqcn = NodeHelpers::fetchClassName($traitName->getAttribute('resolvedName'));
            $traitFqcn = $this->typeAnalyzer->getNormalizedFqcn($traitFqcn);

            if (isset($this->traitsUsed[$traitFqcn])) {
                continue; // Don't index the same trait twice to avoid duplicates.
            }

            $this->traitsUsed[$traitFqcn] = true;

            $linkEntity = $this->findStructureByFqcn($traitFqcn);

            if ($linkEntity !== null && $linkEntity instanceof Structures\Trait_) {
                $classlike->addTrait($linkEntity);
            } else {
                $classlike->addTraitFqcn($traitFqcn);
            }
        }

        $accessModifierMap = $this->getAccessModifierMap();

        foreach ($node->adaptations as $adaptation) {
            if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
                $traitFqcn = $adaptation->trait !== null ?
                    NodeHelpers::fetchClassName($adaptation->trait->getAttribute('resolvedName')) :
                    null;

                $traitFqcn = $traitFqcn !== null ? $this->typeAnalyzer->getNormalizedFqcn($traitFqcn) : null;

                $accessModifier = null;

                if ($adaptation->newModifier === 1) {
                    $accessModifier = AccessModifierNameValue::PUBLIC_;
                } elseif ($adaptation->newModifier === 2) {
                    $accessModifier = AccessModifierNameValue::PROTECTED_;
                } elseif ($adaptation->newModifier === 4) {
                    $accessModifier = AccessModifierNameValue::PRIVATE_;
                }

                if ($classlike instanceof Structures\Class_) {
                    $traitAlias = new Structures\ClassTraitAlias(
                        $classlike,
                        $traitFqcn,
                        $accessModifier !== null ? $accessModifierMap[$accessModifier] : null,
                        $adaptation->method,
                        $adaptation->newName
                    );
                } else /*if ($classlike instanceof Structures\Trait_)*/ {
                    $traitAlias = new Structures\TraitTraitAlias(
                        $classlike,
                        $traitFqcn,
                        $accessModifier !== null ? $accessModifierMap[$accessModifier] : null,
                        $adaptation->method,
                        $adaptation->newName
                    );
                }

                $this->storage->persist($traitAlias);
            } elseif ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
                $traitFqcn = NodeHelpers::fetchClassName($adaptation->trait->getAttribute('resolvedName'));
                $traitFqcn = $this->typeAnalyzer->getNormalizedFqcn($traitFqcn);

                if ($classlike instanceof Structures\Class_) {
                    $traitPrecedence = new Structures\ClassTraitPrecedence(
                        $classlike,
                        $traitFqcn,
                        $adaptation->method
                    );
                } else /*if ($classlike instanceof Structures\Trait_)*/ {
                    $traitPrecedence = new Structures\TraitTraitPrecedence(
                        $classlike,
                        $traitFqcn,
                        $adaptation->method
                    );
                }

                $this->storage->persist($traitPrecedence);
            }
        }
    }

    /**
     * @param Node\Stmt\Property $node
     *
     * @return void
     */
    private function parseClassPropertyNode(Node\Stmt\Property $node): void
    {
        foreach ($node->props as $i => $property) {
            // Let the first property include the access modifier and other parts, so we are consistent with methods.
            $startOffset = $i === 0 ? $node->getAttribute('startFilePos') : $property->getAttribute('startFilePos') - 1;

            $range = new Range(
                Position::createFromByteOffset(
                    $startOffset,
                    $this->textDocumentItem->getText(),
                    PositionEncoding::VALUE
                ),
                Position::createFromByteOffset(
                    $property->getAttribute('endFilePos') + 1,
                    $this->textDocumentItem->getText(),
                    PositionEncoding::VALUE
                )
            );

            $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

            $defaultValue = $property->default !== null ?
                substr(
                    $this->textDocumentItem->getText(),
                    $property->default->getAttribute('startFilePos'),
                    $property->default->getAttribute('endFilePos') -
                        $property->default->getAttribute('startFilePos') +
                        1
                ) :
                null;

            $docComment = $node->getDocComment() !== null ? $node->getDocComment()->getText() : null;

            $documentation = $this->docblockParser->parse($docComment, [
                DocblockParser::VAR_TYPE,
                DocblockParser::DEPRECATED,
                DocblockParser::DESCRIPTION,
            ], $property->name);

            $varDocumentation = isset($documentation['var']['$' . $property->name]) ?
                $documentation['var']['$' . $property->name] :
                null;

            $shortDescription = $documentation['descriptions']['short'];

            $typeStringSpecification = null;

            if ($varDocumentation) {
                // You can place documentation after the @var tag as well as at the start of the docblock. Fall back
                // from the latter to the former.
                if ($varDocumentation['description'] !== '' && $varDocumentation['description'] !== null) {
                    $shortDescription = $varDocumentation['description'];
                }

                $typeStringSpecification = $varDocumentation['type'];
            } elseif ($node->type !== null) {
                $typeNode = $node->type;

                if ($typeNode instanceof Node\NullableType) {
                    $typeNode = $typeNode->type;
                }

                if ($typeNode instanceof Node\Name) {
                    $typeStringSpecification = NodeHelpers::fetchClassName($typeNode);
                } elseif ($typeNode instanceof Node\Identifier) {
                    $typeStringSpecification = $typeNode->name;
                }

                if ($node->type instanceof Node\NullableType) {
                    $typeStringSpecification .= '|null';
                }
            } elseif ($property->default !== null) {
                $typeList = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                    $property->default,
                    $this->textDocumentItem
                ));

                $typeStringSpecification = implode('|', $typeList);
            }

            if ($typeStringSpecification) {
                $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

                $docblockType = $this->docblockTypeParser->parse($typeStringSpecification);

                $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
            } else {
                $type = new MixedDocblockType();
            }

            $accessModifierMap = $this->getAccessModifierMap();

            $accessModifier = null;

            if ($node->isPublic()) {
                $accessModifier = AccessModifierNameValue::PUBLIC_;
            } elseif ($node->isProtected()) {
                $accessModifier = AccessModifierNameValue::PROTECTED_;
            } elseif ($node->isPrivate()) {
                $accessModifier = AccessModifierNameValue::PRIVATE_;
            }

            $property = new Structures\Property(
                $property->name,
                $this->file,
                $range,
                $defaultValue,
                $documentation['deprecated'],
                false,
                $node->isStatic(),
                $docComment !== '' && $docComment !== null,
                $shortDescription !== '' ? $shortDescription : null,
                ((bool) $documentation['descriptions']['long']) ? $documentation['descriptions']['long'] : null,
                $varDocumentation ? $varDocumentation['description'] : null,
                $this->classlikeStack->peek(),
                $accessModifierMap[$accessModifier],
                $type
            );

            $this->storage->persist($property);
        }
    }

    /**
     * @param Node\Stmt\ClassMethod $node
     *
     * @return void
     */
    private function parseClassMethodNode(Node\Stmt\ClassMethod $node): void
    {
        $docComment = $node->getDocComment() !== null ? $node->getDocComment()->getText() : null;

        $returnTypeHint = null;
        $nodeType = $node->getReturnType();

        if ($nodeType instanceof Node\NullableType) {
            $returnTypeHint = '?';
            $nodeType = $nodeType->type;
        }

        if ($nodeType instanceof Node\Name) {
            $returnTypeHint .= NodeHelpers::fetchClassName($nodeType->getAttribute('resolvedName'));
        } elseif ($nodeType instanceof Node\Identifier) {
            $returnTypeHint .= $nodeType->name;
        }

        $range = new Range(
            Position::createFromByteOffset(
                $node->getAttribute('startFilePos'),
                $this->textDocumentItem->getText(),
                PositionEncoding::VALUE
            ),
            Position::createFromByteOffset(
                $node->getAttribute('endFilePos') + 1,
                $this->textDocumentItem->getText(),
                PositionEncoding::VALUE
            )
        );

        $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::THROWS,
            DocblockParser::PARAM_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION,
            DocblockParser::RETURN_VALUE,
        ], $node->name);

        $typeStringSpecification = null;

        if ($documentation['return'] !== null && $documentation['return']['type'] !== null) {
            $typeStringSpecification = $documentation['return']['type'];
        } elseif ($node->getReturnType() !== null) {
            $nodeType = $node->getReturnType();

            if ($nodeType instanceof Node\NullableType) {
                $nodeType = $nodeType->type;
            }

            if ($nodeType instanceof Node\Name) {
                $typeStringSpecification = NodeHelpers::fetchClassName($nodeType);
            } elseif ($nodeType instanceof Node\Identifier) {
                $typeStringSpecification = $nodeType->name;
            }

            $typeStringSpecification = $nodeType->toString();

            if ($node->getReturnType() instanceof Node\NullableType) {
                $typeStringSpecification .= '|null';
            }
        }

        if ($typeStringSpecification) {
            $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

            $docblockType = $this->docblockTypeParser->parse($typeStringSpecification);

            $returnType = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
        } elseif ($docComment !== null) {
            $returnType = new VoidDocblockType();
        } else {
            $returnType = new MixedDocblockType();
        }

        $throws = [];

        foreach ($documentation['throws'] as $throw) {
            $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

            $docblockType = $this->docblockTypeParser->parse($throw['type']);

            $localType = $docblockType->toString();

            $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);

            $throws[] = new Structures\ThrowsInfo(
                $localType,
                $type->toString(),
                $throw['description'] !== '' ? $throw['description'] : null
            );
        }

        $accessModifierMap = $this->getAccessModifierMap();

        $accessModifier = null;

        if ($node->isPublic()) {
            $accessModifier = AccessModifierNameValue::PUBLIC_;
        } elseif ($node->isProtected()) {
            $accessModifier = AccessModifierNameValue::PROTECTED_;
        } elseif ($node->isPrivate()) {
            $accessModifier = AccessModifierNameValue::PRIVATE_;
        }

        $method = new Structures\Method(
            $node->name->name,
            $this->file,
            $range,
            $documentation['deprecated'],
            ((bool) $documentation['descriptions']['short']) ? $documentation['descriptions']['short'] : null,
            ((bool) $documentation['descriptions']['long']) ? $documentation['descriptions']['long'] : null,
            $documentation['return']['description'] ?? null,
            $returnTypeHint,
            $this->classlikeStack->peek(),
            $accessModifier !== null? $accessModifierMap[$accessModifier] : null,
            false,
            $node->isStatic(),
            $node->isAbstract(),
            $node->isFinal(),
            $docComment !== '' && $docComment !== null,
            $throws,
            $returnType
        );

        $this->storage->persist($method);

        foreach ($node->getParams() as $param) {
            $typeHint = null;
            $typeNode = $param->type;

            if ($typeNode instanceof Node\NullableType) {
                $typeHint = '?';
                $typeNode = $typeNode->type;
            }

            if ($typeNode instanceof Node\Name) {
                $typeHint .= NodeHelpers::fetchClassName($typeNode->getAttribute('resolvedName'));
            } elseif ($typeNode instanceof Node\Identifier) {
                $typeHint .= $typeNode->name;
            }

            $isNullable = (
                ($param->type instanceof Node\NullableType) ||
                ($param->default instanceof Node\Expr\ConstFetch && $param->default->name->toString() === 'null')
            );

            $defaultValue = $param->default !== null?
                substr(
                    $this->textDocumentItem->getText(),
                    $param->default->getAttribute('startFilePos'),
                    $param->default->getAttribute('endFilePos') - $param->default->getAttribute('startFilePos') + 1
                ) :
                null;

            $parameterName = ($param->var instanceof Node\Expr\Variable ? $param->var->name : '');
            $parameterKey = '$' . $parameterName;
            $parameterDoc = isset($documentation['params'][$parameterKey]) ?
                $documentation['params'][$parameterKey] : null;

            $typeStringSpecification = null;

            if ($parameterDoc) {
                $typeStringSpecification = $parameterDoc['type'];
            } elseif ($param->type !== null) {
                $typeNode = $param->type;

                if ($typeNode instanceof Node\NullableType) {
                    $typeNode = $typeNode->type;
                }

                if ($typeNode instanceof Node\Name) {
                    $typeStringSpecification = NodeHelpers::fetchClassName($typeNode);
                } elseif ($typeNode instanceof Node\Identifier) {
                    $typeStringSpecification = $typeNode->name;
                }

                if ($param->type instanceof Node\NullableType) {
                    $typeStringSpecification .= '|null';
                } elseif ($param->default instanceof Node\Expr\ConstFetch &&
                    $param->default->name->toString() === 'null'
                ) {
                    $typeStringSpecification .= '|null';
                }
            } elseif ($param->default !== null) {
                $typeList = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                    $param->default,
                    $this->textDocumentItem
                ));

                $typeStringSpecification = implode('|', $typeList);
            }

            if ($typeStringSpecification) {
                $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

                $docblockType = $this->docblockTypeParser->parse($typeStringSpecification);

                $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
            } else {
                $type = new MixedDocblockType();
            }

            if ($param->variadic) {
                $type = new SpecializedArrayDocblockType($type);
            }

            $parameter = new Structures\MethodParameter(
                $method,
                $param->var instanceof Node\Expr\Variable ? $param->var->name : '',
                $typeHint,
                $type,
                $parameterDoc ? $parameterDoc['description'] : null,
                $defaultValue,
                $param->byRef,
                $param->default !== null,
                $param->variadic
            );

            $this->storage->persist($parameter);
        }
    }

    /**
     * @param Node\Stmt\ClassConst $node
     *
     * @return void
     */
    private function parseClassConstantStatementNode(Node\Stmt\ClassConst $node): void
    {
        foreach ($node->consts as $const) {
            $this->parseClassConstantNode($const, $node);
        }
    }

    /**
     * @param Node\Const_          $node
     * @param Node\Stmt\ClassConst $classConst
     *
     * @return void
     */
    private function parseClassConstantNode(Node\Const_ $node, Node\Stmt\ClassConst $classConst): void
    {
        $range = new Range(
            Position::createFromByteOffset(
                $node->getAttribute('startFilePos'),
                $this->textDocumentItem->getText(),
                PositionEncoding::VALUE
            ),
            Position::createFromByteOffset(
                $node->getAttribute('endFilePos') + 1,
                $this->textDocumentItem->getText(),
                PositionEncoding::VALUE
            )
        );

        $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

        $docComment = $classConst->getDocComment() !== null ? $classConst->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::VAR_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION,
        ], $node->name->name);

        $varDocumentation = isset($documentation['var']['$' . $node->name->name]) ?
            $documentation['var']['$' . $node->name->name] :
            null;

        $shortDescription = $documentation['descriptions']['short'];

        $defaultValue = substr(
            $this->textDocumentItem->getText(),
            $node->value->getAttribute('startFilePos'),
            $node->value->getAttribute('endFilePos') - $node->value->getAttribute('startFilePos') + 1
        );

        $typeStringSpecification = null;

        if ($varDocumentation) {
            // You can place documentation after the @var tag as well as at the start of the docblock. Fall back
            // from the latter to the former.
            if ($varDocumentation['description'] !== null && $varDocumentation['description'] !== '') {
                $shortDescription = $varDocumentation['description'];
            }

            $typeStringSpecification = $varDocumentation['type'];
        } else {
            $typeList = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                $node->value,
                $this->textDocumentItem
            ));

            $typeStringSpecification = implode('|', $typeList);
        }

        $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

        if ($typeStringSpecification) {
            $docblockType = $this->docblockTypeParser->parse($typeStringSpecification);

            $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
        } else {
            $type = new MixedDocblockType();
        }

        $accessModifierMap = $this->getAccessModifierMap();

        $accessModifier = null;

        if ($classConst->isPublic()) {
            $accessModifier = AccessModifierNameValue::PUBLIC_;
        } elseif ($classConst->isProtected()) {
            $accessModifier = AccessModifierNameValue::PROTECTED_;
        } elseif ($classConst->isPrivate()) {
            $accessModifier = AccessModifierNameValue::PRIVATE_;
        }

        $constant = new Structures\ClassConstant(
            $node->name->name,
            $this->file,
            $range,
            $defaultValue,
            $documentation['deprecated'],
            $docComment !== '' && $docComment !== null,
            $shortDescription !== '' ? $shortDescription : null,
            ((bool) $documentation['descriptions']['long']) ? $documentation['descriptions']['long'] : null,
            $varDocumentation ? $varDocumentation['description'] : null,
            $type,
            $this->classlikeStack->peek(),
            $accessModifier !== null? $accessModifierMap[$accessModifier] : null
        );

        $this->storage->persist($constant);
    }

    /**
     * @param array                     $rawData
     * @param Structures\Classlike      $classlike
     * @param Structures\AccessModifier $accessModifier
     * @param FilePosition              $filePosition
     *
     * @return void
     */
    private function indexMagicProperty(
        array $rawData,
        Structures\Classlike $classlike,
        Structures\AccessModifier $accessModifier,
        FilePosition $filePosition
    ): void {
        $type = new MixedDocblockType();

        if ($rawData['type']) {
            $docblockType = $this->docblockTypeParser->parse($rawData['type']);

            $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
        }

        $property = new Structures\Property(
            $rawData['name'],
            $this->file,
            new Range(
                new Position($filePosition->getPosition()->getLine() - 1, 0),
                new Position($filePosition->getPosition()->getLine() - 1, 0)
            ),
            null,
            false,
            true,
            $rawData['isStatic'],
            false,
            $rawData['description'] !== '' ? $rawData['description'] : null,
            null,
            null,
            $classlike,
            $accessModifier,
            $type
        );

        $this->storage->persist($property);
    }

    /**
     * @param array                     $rawData
     * @param Structures\Classlike      $classlike
     * @param Structures\AccessModifier $accessModifier
     * @param FilePosition              $filePosition
     *
     * @return void
     */
    private function indexMagicMethod(
        array $rawData,
        Structures\Classlike $classlike,
        Structures\AccessModifier $accessModifier,
        FilePosition $filePosition
    ): void {
        $returnType = new MixedDocblockType();

        if ($rawData['type']) {
            $docblockType = $this->docblockTypeParser->parse($rawData['type']);

            $returnType = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
        }

        $method = new Structures\Method(
            $rawData['name'],
            $this->file,
            new Range(
                new Position($filePosition->getPosition()->getLine() - 1, 0),
                new Position($filePosition->getPosition()->getLine() - 1, 0)
            ),
            false,
            $rawData['description'],
            null,
            null,
            null,
            $classlike,
            $accessModifier,
            true,
            $rawData['isStatic'],
            false,
            false,
            false,
            [],
            $returnType
        );

        $this->storage->persist($method);

        foreach ($rawData['requiredParameters'] as $parameterName => $parameter) {
            $type = new MixedDocblockType();

            if ($parameter['type']) {
                $docblockType = $this->docblockTypeParser->parse($parameter['type']);

                $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
            }

            $parameter = new Structures\MethodParameter(
                $method,
                mb_substr($parameterName, 1),
                null,
                $type,
                null,
                null,
                false,
                false,
                false
            );

            $this->storage->persist($parameter);
        }

        foreach ($rawData['optionalParameters'] as $parameterName => $parameter) {
            $type = new MixedDocblockType();

            if ($parameter['type']) {
                $docblockType = $this->docblockTypeParser->parse($parameter['type']);

                $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
            }

            $parameter = new Structures\MethodParameter(
                $method,
                mb_substr($parameterName, 1),
                null,
                $type,
                null,
                null,
                false,
                true,
                false
            );

            $this->storage->persist($parameter);
        }
    }

    /**
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function indexClassKeyword(Structures\Classlike $classlike): void
    {
        $constant = new Structures\ClassConstant(
            'class',
            $this->file,
            new Range(
                new Position($classlike->getRange()->getStart()->getLine(), 0),
                new Position($classlike->getRange()->getStart()->getLine(), 0)
            ),
            '\'' . mb_substr($classlike->getFqcn(), 1) . '\'',
            false,
            false,
            'PHP built-in class constant that evaluates to the FQCN.',
            null,
            null,
            new StringDocblockType(),
            $classlike,
            $this->getAccessModifierMap()[AccessModifierNameValue::PUBLIC_]
        );

        $this->storage->persist($constant);
    }

    /**
     * @return array
     */
    private function getAccessModifierMap(): array
    {
        if ($this->accessModifierMap === null) {
            $modifiers = $this->storage->getAccessModifiers();

            $this->accessModifierMap = [];

            foreach ($modifiers as $type) {
                $this->accessModifierMap[$type->getName()] = $type;
            }
        }

        return $this->accessModifierMap;
    }

    /**
     * @param string $fqcn
     *
     * @return Structures\Classlike|null
     */
    private function findStructureByFqcn(string $fqcn): ?Structures\Classlike
    {
        foreach ($this->classlikesFound as $classlike) {
            $foundFqcn = $this->classlikesFound[$classlike];

            if ($fqcn === $foundFqcn) {
                return $classlike;
            }
        }

        return $this->storage->findStructureByFqcn($fqcn);
    }
}
