<?php

namespace Serenata\Indexing\Visiting;

use DomainException;
use SplObjectStorage;

use Ds\Stack;

use Serenata\Common\Range;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\SourceCodeHelpers;

use Serenata\Analysis\Typing\TypeAnalyzer;

use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use Serenata\Indexing\Structures\AccessModifierNameValue;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Parsing\DocblockParser;

use Serenata\Utility\NodeHelpers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that traverses a set of nodes, indexing classlikes in the process.
 */
final class ClasslikeIndexingVisitor extends NodeVisitorAbstract
{
    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var array
     */
    private $accessModifierMap;

    /**
     * @var Structures\File
     */
    private $file;

    /**
     * @var string
     */
    private $code;

    /**
     * @var Stack Stack<Structures\Classlike>
     */
    private $classlikeStack;

    /**
     * @var string[]
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
     * @param NodeTypeDeducerInterface                   $nodeTypeDeducer
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param Structures\File                            $file
     * @param string                                     $code
     */
    public function __construct(
        StorageInterface $storage,
        TypeAnalyzer $typeAnalyzer,
        DocblockParser $docblockParser,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        Structures\File $file,
        string $code
    ) {
        $this->storage = $storage;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockParser = $docblockParser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->file = $file;
        $this->code = $code;
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
    }

    /**
     * @inheritDoc
     */
    public function leaveNode(\PhpParser\Node $node)
    {
        $value = parent::leaveNode($node);

        if ($node instanceof Node\Stmt\Class_) {
            $this->processLeaveClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $this->processLeaveClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->processLeaveClasslikeNode($node);
        }
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

        $docComment = $node->getDocComment() ? $node->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::DEPRECATED,
            DocblockParser::ANNOTATION,
            DocblockParser::DESCRIPTION,
            DocblockParser::METHOD,
            DocblockParser::PROPERTY,
            DocblockParser::PROPERTY_READ,
            DocblockParser::PROPERTY_WRITE
        ], '');

        $classlike = null;

        if ($node instanceof Node\Stmt\Class_) {
            $fqcn = null;
            $classlikeName = null;

            if ($node->isAnonymous()) {
                $fqcn = NodeHelpers::getFqcnForAnonymousClassNode($node, $this->file->getPath());
                $classlikeName = mb_substr($fqcn, 1);
            } else {
                $fqcn = '\\' . $node->namespacedName->toString();
                $classlikeName = $node->name->name;
            }

            $classlike = new Structures\Class_(
                $classlikeName,
                $fqcn,
                $this->file,
                $node->getLine(),
                $node->getAttribute('endLine'),
                $documentation['descriptions']['short'] ?: null,
                $documentation['descriptions']['long'] ?: null,
                $node->isAnonymous(),
                $node->isAbstract(),
                $node->isFinal(),
                $documentation['annotation'],
                $documentation['deprecated'],
                !empty($docComment),
                null
            );
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $classlike = new Structures\Interface_(
                $node->name->name,
                '\\' . $node->namespacedName->toString(),
                $this->file,
                $node->getLine(),
                $node->getAttribute('endLine'),
                $documentation['descriptions']['short'] ?: null,
                $documentation['descriptions']['long'] ?: null,
                $documentation['deprecated'],
                !empty($docComment)
            );
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $classlike = new Structures\Trait_(
                $node->name->name,
                '\\' . $node->namespacedName->toString(),
                $this->file,
                $node->getLine(),
                $node->getAttribute('endLine'),
                $documentation['descriptions']['short'] ?: null,
                $documentation['descriptions']['long'] ?: null,
                $documentation['deprecated'],
                !empty($docComment)
            );
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

        $filePosition = new FilePosition($this->file->getPath(), new Position($node->getLine(), 0));

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
            $this->processClassRelations($node, $classlike);
        } elseif ($classlike instanceof Structures\Interface_) {
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
        if ($node->extends) {
            $parent = NodeHelpers::fetchClassName($node->extends->getAttribute('resolvedName'));

            $parentFqcn = $this->typeAnalyzer->getNormalizedFqcn($parent);

            $linkEntity = $this->findStructureByFqcn($parentFqcn);

            if ($linkEntity && $linkEntity instanceof Structures\Class_) {
                $class->setParent($linkEntity);
            } else {
                $class->setParentFqcn($parentFqcn);
            }
        }

        $implementedFqcns = array_unique(array_map(function (Node\Name $name) {
            $resolvedName = NodeHelpers::fetchClassName($name->getAttribute('resolvedName'));

            return $this->typeAnalyzer->getNormalizedFqcn($resolvedName);
        }, $node->implements));

        foreach ($implementedFqcns as $implementedFqcn) {
            $linkEntity = $this->findStructureByFqcn($implementedFqcn);

            if ($linkEntity && $linkEntity instanceof Structures\Interface_) {
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
        $extendedFqcns = array_unique(array_map(function (Node\Name $name) {
            $resolvedName = NodeHelpers::fetchClassName($name->getAttribute('resolvedName'));

            return $this->typeAnalyzer->getNormalizedFqcn($resolvedName);
        }, $node->extends));

        foreach ($extendedFqcns as $extendedFqcn) {
            $linkEntity = $this->findStructureByFqcn($extendedFqcn);

            if ($linkEntity && $linkEntity instanceof Structures\Interface_) {
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
        if ($classlike instanceof Structures\Interface_) {
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

            if ($linkEntity && $linkEntity instanceof Structures\Trait_) {
                $classlike->addTrait($linkEntity);
            } else {
                $classlike->addTraitFqcn($traitFqcn);
            }
        }

        $accessModifierMap = $this->getAccessModifierMap();

        foreach ($node->adaptations as $adaptation) {
            if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
                $traitFqcn = $adaptation->trait ? NodeHelpers::fetchClassName($adaptation->trait->getAttribute('resolvedName')) : null;
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
                        $accessModifier ? $accessModifierMap[$accessModifier] : null,
                        $adaptation->method,
                        $adaptation->newName
                    );
                } elseif ($classlike instanceof Structures\Trait_) {
                    $traitAlias = new Structures\TraitTraitAlias(
                        $classlike,
                        $traitFqcn,
                        $accessModifier ? $accessModifierMap[$accessModifier] : null,
                        $adaptation->method,
                        $adaptation->newName
                    );
                } else {
                    continue; // Can't add trait aliases in any other classlike type.
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
                } elseif ($classlike instanceof Structures\Trait_) {
                    $traitPrecedence = new Structures\TraitTraitPrecedence(
                        $classlike,
                        $traitFqcn,
                        $adaptation->method
                    );
                } else {
                    continue; // Can't add trait precedences in any other classlike type.
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
        $filePosition = new FilePosition($this->file->getPath(), new Position($node->getLine(), 0));

        foreach ($node->props as $property) {
            $defaultValue = $property->default ?
                substr(
                    $this->code,
                    $property->default->getAttribute('startFilePos'),
                    $property->default->getAttribute('endFilePos') - $property->default->getAttribute('startFilePos') + 1
                ) :
                null;

            $docComment = $node->getDocComment() ? $node->getDocComment()->getText() : null;

            $documentation = $this->docblockParser->parse($docComment, [
                DocblockParser::VAR_TYPE,
                DocblockParser::DEPRECATED,
                DocblockParser::DESCRIPTION
            ], $property->name);

            $varDocumentation = isset($documentation['var']['$' . $property->name]) ?
                $documentation['var']['$' . $property->name] :
                null;

            $shortDescription = $documentation['descriptions']['short'];

            $types = [];

            if ($varDocumentation) {
                // You can place documentation after the @var tag as well as at the start of the docblock. Fall back
                // from the latter to the former.
                if (!empty($varDocumentation['description'])) {
                    $shortDescription = $varDocumentation['description'];
                }

                $types = $this->getTypeDataForTypeSpecification($varDocumentation['type'], $filePosition);
            } elseif ($property->default) {
                $typeList = $this->nodeTypeDeducer->deduce($property->default, $this->file, $this->code, 0);

                $types = array_map(function (string $type) {
                    return new Structures\TypeInfo($type, $type);
                }, $typeList);
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
                new Range(
                    new Position(
                        $property->getLine() - 1,
                        SourceCodeHelpers::getCharacterOnLineFromByteOffset(
                            $property->getAttribute('startFilePos'),
                            $this->code,
                            PositionEncoding::VALUE
                        )
                    ),
                    new Position(
                        $property->getAttribute('endLine') - 1,
                        SourceCodeHelpers::getCharacterOnLineFromByteOffset(
                            $property->getAttribute('endFilePos'),
                            $this->code,
                            PositionEncoding::VALUE
                        ) + 2
                    )
                ),
                $defaultValue,
                $documentation['deprecated'],
                false,
                $node->isStatic(),
                !empty($docComment),
                $shortDescription ?: null,
                $documentation['descriptions']['long'] ?: null,
                $varDocumentation ? $varDocumentation['description'] : null,
                $this->classlikeStack->peek(),
                $accessModifierMap[$accessModifier],
                $types
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
        $localType = null;
        $resolvedType = null;
        $returnTypeHint = null;
        $nodeType = $node->getReturnType();

        if ($nodeType instanceof Node\NullableType) {
            $nodeType = $nodeType->type;
            $returnTypeHint = '?';
        }

        if ($nodeType instanceof Node\Name) {
            $localType = NodeHelpers::fetchClassName($nodeType);
            $resolvedType = NodeHelpers::fetchClassName($nodeType->getAttribute('resolvedName'));
            $returnTypeHint .= $resolvedType;
        } elseif ($nodeType instanceof Node\Identifier) {
            $localType = $nodeType->name;
            $resolvedType = $nodeType->name;
            $returnTypeHint .= $resolvedType;
        }

        $filePosition = new FilePosition($this->file->getPath(), new Position($node->getLine(), 0));

        $isReturnTypeNullable = ($node->getReturnType() instanceof Node\NullableType);
        $docComment = $node->getDocComment() ? $node->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::THROWS,
            DocblockParser::PARAM_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION,
            DocblockParser::RETURN_VALUE
        ], $node->name->name);

        $returnTypes = [];

        if ($documentation && $documentation['return']['type']) {
            $returnTypes = $this->getTypeDataForTypeSpecification($documentation['return']['type'], $filePosition);
        } elseif ($localType) {
            $returnTypes = [
                new Structures\TypeInfo($localType, $resolvedType ?: $localType)
            ];

            if ($isReturnTypeNullable) {
                $returnTypes[] = new Structures\TypeInfo('null', 'null');
            }
        }

        $throws = [];

        foreach ($documentation['throws'] as $throw) {
            $typeData = $this->getTypeDataForTypeSpecification($throw['type'], $filePosition);
            $typeData = array_shift($typeData);

            $throws[] = new Structures\ThrowsInfo(
                $typeData->getType(),
                $typeData->getFqcn(),
                $throw['description'] ?: null
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
            new Range(
                new Position(
                    $node->getLine() - 1,
                    SourceCodeHelpers::getCharacterOnLineFromByteOffset(
                        $node->getAttribute('startFilePos'),
                        $this->code,
                        PositionEncoding::VALUE
                    )
                ),
                new Position(
                    $node->getAttribute('endLine') - 1,
                    SourceCodeHelpers::getCharacterOnLineFromByteOffset(
                        $node->getAttribute('endFilePos'),
                        $this->code,
                        PositionEncoding::VALUE
                    ) + 2
                )
            ),
            $documentation['deprecated'],
            $documentation['descriptions']['short'] ?: null,
            $documentation['descriptions']['long'] ?: null,
            $documentation['return']['description'] ?: null,
            $returnTypeHint,
            $this->classlikeStack->peek(),
            $accessModifier ? $accessModifierMap[$accessModifier] : null,
            false,
            $node->isStatic(),
            $node->isAbstract(),
            $node->isFinal(),
            !empty($docComment),
            $throws,
            $returnTypes
        );

        $this->storage->persist($method);

        foreach ($node->getParams() as $param) {
            $typeHint = null;
            $localType = null;
            $resolvedType = null;

            $typeNode = $param->type;

            if ($typeNode instanceof Node\NullableType) {
                $typeNode = $typeNode->type;
                $typeHint = '?';
            }

            if ($typeNode instanceof Node\Name) {
                $localType = NodeHelpers::fetchClassName($typeNode);
                $resolvedType = NodeHelpers::fetchClassName($typeNode->getAttribute('resolvedName'));
                $typeHint .= $resolvedType;
            } elseif ($typeNode instanceof Node\Identifier) {
                $localType = $typeNode->name;
                $resolvedType = $typeNode->name;
                $typeHint .= $resolvedType;
            }

            $isNullable = (
                ($param->type instanceof Node\NullableType) ||
                ($param->default instanceof Node\Expr\ConstFetch && $param->default->name->toString() === 'null')
            );

            $defaultValue = $param->default ?
                substr(
                    $this->code,
                    $param->default->getAttribute('startFilePos'),
                    $param->default->getAttribute('endFilePos') - $param->default->getAttribute('startFilePos') + 1
                ) :
                null;

            $parameterKey = '$' . $param->var->name;
            $parameterDoc = isset($documentation['params'][$parameterKey]) ?
                $documentation['params'][$parameterKey] : null;

            $types = [];

            if ($parameterDoc) {
                $types = $this->getTypeDataForTypeSpecification($parameterDoc['type'], $filePosition);
            } elseif ($localType) {
                $parameterType = $localType;
                $parameterFullType = $resolvedType ?: $parameterType;

                if ($param->variadic) {
                    $parameterType .= '[]';
                    $parameterFullType .= '[]';
                }

                $types = [
                    new Structures\TypeInfo($parameterType, $parameterFullType)
                ];

                if ($isNullable) {
                    $types[] = new Structures\TypeInfo('null', 'null');
                }
            } elseif ($param->default !== null) {
                $typeList = $this->nodeTypeDeducer->deduce($param->default, $this->file, $this->code, 0);

                $types = array_map(function (string $type) {
                    return new Structures\TypeInfo($type, $type);
                }, $typeList);
            }

            $parameter = new Structures\MethodParameter(
                $method,
                $param->var instanceof Node\Expr\Variable ? $param->var->name : '',
                $typeHint,
                $types,
                $parameterDoc ? $parameterDoc['description'] : null,
                $defaultValue,
                $param->byRef,
                !!$param->default,
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
        $filePosition = new FilePosition($this->file->getPath(), new Position($node->getLine(), 0));

        $docComment = $classConst->getDocComment() ? $classConst->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::VAR_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION
        ], $node->name->name);

        $varDocumentation = isset($documentation['var']['$' . $node->name->name]) ?
            $documentation['var']['$' . $node->name->name] :
            null;

        $shortDescription = $documentation['descriptions']['short'];

        $types = [];

        $defaultValue = substr(
            $this->code,
            $node->value->getAttribute('startFilePos'),
            $node->value->getAttribute('endFilePos') - $node->value->getAttribute('startFilePos') + 1
        );

        if ($varDocumentation) {
            // You can place documentation after the @var tag as well as at the start of the docblock. Fall back
            // from the latter to the former.
            if (!empty($varDocumentation['description'])) {
                $shortDescription = $varDocumentation['description'];
            }

            $types = $this->getTypeDataForTypeSpecification($varDocumentation['type'], $filePosition);
        } elseif ($node->value) {
            $typeList = $this->nodeTypeDeducer->deduce($node->value, $this->file, $this->code, 0);

            $types = array_map(function (string $type) {
                return new Structures\TypeInfo($type, $type);
            }, $typeList);
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
            new Range(
                new Position(
                    $node->getLine() - 1,
                    SourceCodeHelpers::getCharacterOnLineFromByteOffset(
                        $node->getAttribute('startFilePos'),
                        $this->code,
                        PositionEncoding::VALUE
                    )
                ),
                new Position(
                    $node->getAttribute('endLine') - 1,
                    SourceCodeHelpers::getCharacterOnLineFromByteOffset(
                        $node->getAttribute('endFilePos'),
                        $this->code,
                        PositionEncoding::VALUE
                    ) + 2
                )
            ),
            $defaultValue,
            $documentation['deprecated'] ? 1 : 0,
            !empty($docComment),
            $shortDescription ?: null,
            $documentation['descriptions']['long'] ?: null,
            $varDocumentation ? $varDocumentation['description'] : null,
            $types,
            $this->classlikeStack->peek(),
            $accessModifier ? $accessModifierMap[$accessModifier] : null
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
        $types = [];

        if ($rawData['type']) {
            $types = $this->getTypeDataForTypeSpecification($rawData['type'], $filePosition);
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
            $rawData['description'] ?: null,
            null,
            null,
            $classlike,
            $accessModifier,
            $types
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
        $returnTypes = [];

        if ($rawData['type']) {
            $returnTypes = $this->getTypeDataForTypeSpecification($rawData['type'], $filePosition);
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
            $returnTypes
        );

        $this->storage->persist($method);

        foreach ($rawData['requiredParameters'] as $parameterName => $parameter) {
            $types = [];

            if ($parameter['type']) {
                $types = $this->getTypeDataForTypeSpecification($parameter['type'], $filePosition);
            }

            $parameter = new Structures\MethodParameter(
                $method,
                mb_substr($parameterName, 1),
                null,
                $types,
                null,
                null,
                false,
                false,
                false
            );

            $this->storage->persist($parameter);
        }

        foreach ($rawData['optionalParameters'] as $parameterName => $parameter) {
            $types = [];

            if ($parameter['type']) {
                $types = $this->getTypeDataForTypeSpecification($parameter['type'], $filePosition);
            }

            $parameter = new Structures\MethodParameter(
                $method,
                mb_substr($parameterName, 1),
                null,
                $types,
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
                new Position($classlike->getStartLine() - 1, 0),
                new Position($classlike->getStartLine() - 1, 0)
            ),
            '\'' . mb_substr($classlike->getFqcn(), 1) . '\'',
            false,
            false,
            'PHP built-in class constant that evaluates to the FQCN.',
            null,
            null,
            [new Structures\TypeInfo('string', 'string')],
            $classlike,
            $this->getAccessModifierMap()[AccessModifierNameValue::PUBLIC_]
        );

        $this->storage->persist($constant);
    }

    /**
     * @param string       $typeSpecification
     * @param FilePosition $filePosition
     *
     * @return Structures\TypeInfo[]
     */
    private function getTypeDataForTypeSpecification(string $typeSpecification, FilePosition $filePosition): array
    {
        $typeList = $this->typeAnalyzer->getTypesForTypeSpecification($typeSpecification);

        return $this->getTypeDataForTypeList($typeList, $filePosition);
    }

    /**
     * @param string[]     $typeList
     * @param FilePosition $filePosition
     *
     * @return Structures\TypeInfo[]
     */
    private function getTypeDataForTypeList(array $typeList, FilePosition $filePosition): array
    {
        $types = [];

        $positionalNameResolver = $this->structureAwareNameResolverFactory->create($filePosition);

        foreach ($typeList as $type) {
            $types[] = new Structures\TypeInfo($type, $positionalNameResolver->resolve($type, $filePosition));
        }

        return $types;
    }

    /**
     * @return array
     */
    private function getAccessModifierMap(): array
    {
        if (!$this->accessModifierMap) {
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
