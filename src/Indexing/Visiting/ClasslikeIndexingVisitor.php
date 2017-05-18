<?php

namespace PhpIntegrator\Indexing\Visiting;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Common\Position;
use PhpIntegrator\Common\FilePosition;

use PhpIntegrator\Indexing\Structures;
use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\IndexStorageItemEnum;

use PhpIntegrator\NameQualificationUtilities\PositionalNameResolverInterface;
use PhpIntegrator\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use PhpIntegrator\Parsing\DocblockParser;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;
use PhpParser\NodeTraverser;
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
     * @var array
     */
    private $structureTypeMap;

    /**
     * @var Structures\File
     */
    private $file;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var string[]
     */
    private $traitsUsed = [];

    /**
     * @param StorageInterface                           $storage
     * @param TypeAnalyzer                               $typeAnalyzer
     * @param DocblockParser                             $docblockParser
     * @param NodeTypeDeducerInterface                   $nodeTypeDeducer
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param Structures\File                            $file
     * @param string                                     $code
     * @param string                                     $filePath
     */
    public function __construct(
        StorageInterface $storage,
        TypeAnalyzer $typeAnalyzer,
        DocblockParser $docblockParser,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        Structures\File $file,
        string $code,
        string $filePath
    ) {
        $this->storage = $storage;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockParser = $docblockParser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->file = $file;
        $this->code = $code;
        $this->filePath = $filePath;
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
            if ($node->isAnonymous()) {
                // Ticket #45 - Skip PHP 7 anonymous classes.
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            $this->parseClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $this->parseClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->parseClasslikeNode($node);
        } elseif ($node instanceof Node\Stmt\TraitUse) {
            $this->parseTraitUseNode($node);
        }
    }

    /**
     * @param Node\Stmt\ClassLike $node
     *
     * @return void
     */
    protected function parseClasslikeNode(Node\Stmt\ClassLike $node): void
    {
        if (!isset($node->namespacedName)) {
            return;
        }

        $this->traitsUsed = [];

        $structureTypeMap = $this->getStructureTypeMap();

        $docComment = $node->getDocComment() ? $node->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::DEPRECATED,
            DocblockParser::ANNOTATION,
            DocblockParser::DESCRIPTION,
            DocblockParser::METHOD,
            DocblockParser::PROPERTY,
            DocblockParser::PROPERTY_READ,
            DocblockParser::PROPERTY_WRITE
        ], $node->name->name);

        $structureType = null;

        if ($node instanceof Node\Stmt\Class_) {
            $structureType = $structureTypeMap['class'];
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $structureType = $structureTypeMap['interface'];
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $structureType = $structureTypeMap['trait'];
        }

        $structure = new Structures\Structure(
            $node->name->name,
            '\\' . $node->namespacedName->toString(),
            $this->file,
            $node->getLine(),
            $node->getAttribute('endLine'),
            $structureType,
            $documentation['descriptions']['short'],
            $documentation['descriptions']['long'],
            false,
            $node instanceof Node\Stmt\Class_ && $node->isAbstract(),
            $node instanceof Node\Stmt\Class_ && $node->isFinal(),
            $documentation['annotation'],
            $documentation['deprecated'],
            !empty($docComment),
            [],
            [],
            [],
            [],
            []
        );

        $this->storage->persist($structure);

        $accessModifierMap = $this->getAccessModifierMap();

        if ($node instanceof Node\Stmt\Class_) {
            if ($node->extends) {
                $parent = NodeHelpers::fetchClassName($node->extends->getAttribute('resolvedName'));

                $parentFqcn = $this->typeAnalyzer->getNormalizedFqcn($parent);

                $linkEntity = $this->storage->findStructureByFqcn($parentFqcn);

                $structure->addParent($linkEntity);
            }

            $implementedFqcns = array_unique(array_map(function (Node\Name $name) {
                $resolvedName = NodeHelpers::fetchClassName($name->getAttribute('resolvedName'));

                return $this->typeAnalyzer->getNormalizedFqcn($resolvedName);
            }, $node->implements));

            foreach ($implementedFqcns as $implementedFqcn) {
                $linkEntity = $this->storage->findStructureByFqcn($implementedFqcn);

                $structure->addInterface($linkEntity);
            }
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $extendedFqcns = array_unique(array_map(function (Node\Name $name) {
                $resolvedName = NodeHelpers::fetchClassName($name->getAttribute('resolvedName'));

                return $this->typeAnalyzer->getNormalizedFqcn($resolvedName);
            }, $node->extends));

            foreach ($extendedFqcns as $extendedFqcn) {
                $linkEntity = $this->storage->findStructureByFqcn($extendedFqcn);

                $structure->addParent($linkEntity);
            }
        }

        // Index magic properties.
        $magicProperties = array_merge(
            $documentation['properties'],
            $documentation['propertiesReadOnly'],
            $documentation['propertiesWriteOnly']
        );

        $filePosition = new FilePosition($this->filePath, new Position($node->getLine(), 0));

        foreach ($magicProperties as $propertyName => $propertyData) {
            // Use the same line as the class definition, it matters for e.g. type resolution.
            $propertyData['name'] = mb_substr($propertyName, 1);

            $this->indexMagicProperty(
                $propertyData,
                $this->file,
                $structure,
                $accessModifierMap['public'],
                $filePosition
            );
        }

        // Index magic methods.
        foreach ($documentation['methods'] as $methodName => $methodData) {
            // Use the same line as the class definition, it matters for e.g. type resolution.
            $methodData['name'] = $methodName;

            $this->indexMagicMethod(
                $methodData,
                $structure,
                $accessModifierMap['public'],
                $filePosition
            );
        }

        $this->structure = $structure;
    }

    /**
     * @param Node\Stmt\TraitUse $node
     *
     * @return void
     */
    protected function parseTraitUseNode(Node\Stmt\TraitUse $node): void
    {
        foreach ($node->traits as $traitName) {
            $traitFqcn = NodeHelpers::fetchClassName($traitName->getAttribute('resolvedName'));
            $traitFqcn = $this->typeAnalyzer->getNormalizedFqcn($traitFqcn);

            if (isset($this->traitsUsed[$traitFqcn])) {
                continue; // Don't index the same trait twice to avoid duplicates.
            }

            $this->traitsUsed[$traitFqcn] = true;

            $linkEntity = $this->storage->findStructureByFqcn($traitFqcn);

            $this->structure->addTrait($linkEntity);
        }

        $accessModifierMap = $this->getAccessModifierMap();

        foreach ($node->adaptations as $adaptation) {
            if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
                $trait = $adaptation->trait ? NodeHelpers::fetchClassName($adaptation->trait->getAttribute('resolvedName')) : null;

                $accessModifier = null;

                if ($adaptation->newModifier === 1) {
                    $accessModifier = 'public';
                } elseif ($adaptation->newModifier === 2) {
                    $accessModifier = 'protected';
                } elseif ($adaptation->newModifier === 4) {
                    $accessModifier = 'private';
                }

                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_TRAITS_ALIASES, [
                    'structure_id'         => $this->structure,
                    'trait_structure_fqcn' => ($trait !== null) ?
                        $this->typeAnalyzer->getNormalizedFqcn($trait) : null,
                    'access_modifier_id'   => $accessModifier ? $accessModifierMap[$accessModifier] : null,
                    'name'                 => $adaptation->method,
                    'alias'                => $adaptation->newName
                ]);
            } elseif ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
                $fqcn = NodeHelpers::fetchClassName($adaptation->trait->getAttribute('resolvedName'));

                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_TRAITS_PRECEDENCES, [
                    'structure_id'         => $this->structure,
                    'trait_structure_fqcn' => $this->typeAnalyzer->getNormalizedFqcn($fqcn),
                    'name'                 => $adaptation->method
                ]);
            }
        }
    }

    /**
     * @param Node\Stmt\Property $node
     *
     * @return void
     */
    protected function parseClassPropertyNode(Node\Stmt\Property $node): void
    {
        $filePosition = new FilePosition($this->filePath, new Position($node->getLine(), 0));

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
                $typeList = $this->nodeTypeDeducer->deduce(
                    $property->default,
                    $this->filePath,
                    $defaultValue,
                    0
                );

                $types = array_map(function (string $type) {
                    return new Structures\TypeInfo($type, $type);
                }, $typeList);
            }

            $accessModifierMap = $this->getAccessModifierMap();

            $accessModifier = null;

            if ($node->isPublic()) {
                $accessModifier = 'public';
            } elseif ($node->isProtected()) {
                $accessModifier = 'protected';
            } elseif ($node->isPrivate()) {
                $accessModifier = 'private';
            }

            $property = new Structures\Property(
                $property->name,
                $this->file,
                $property->getLine(),
                $property->getAttribute('endLine'),
                $defaultValue,
                $documentation['deprecated'],
                false,
                $node->isStatic(),
                !empty($docComment),
                $shortDescription,
                $documentation['descriptions']['long'],
                $varDocumentation ? $varDocumentation['description'] : null,
                $this->structure,
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
    protected function parseClassMethodNode(Node\Stmt\ClassMethod $node): void
    {
        $localType = null;
        $resolvedType = null;
        $nodeType = $node->getReturnType();

        if ($nodeType instanceof Node\NullableType) {
            $nodeType = $nodeType->type;
        }

        if ($nodeType instanceof Node\Name) {
            $localType = NodeHelpers::fetchClassName($nodeType);
            $resolvedType = NodeHelpers::fetchClassName($nodeType->getAttribute('resolvedName'));
        } elseif ($nodeType instanceof Node\Identifier) {
            $localType = $nodeType->name;
            $resolvedType = $nodeType->name;
        }

        $filePosition = new FilePosition($this->filePath, new Position($node->getLine(), 0));

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
                $returnTypes[] = [new Structures\TypeInfo('null', 'null')];
            }
        }

        $throws = [];

        foreach ($documentation['throws'] as $throw) {
            $typeData = $this->getTypeDataForTypeSpecification($throw['type'], $filePosition);
            $typeData = array_shift($typeData);

            $throwsData = [
                'type'        => $typeData['type'],
                'full_type'   => $typeData['fqcn'],
                'description' => $throw['description'] ?: null
            ];

            $throws[] = $throwsData;
        }

        $accessModifierMap = $this->getAccessModifierMap();

        $accessModifier = null;

        if ($node->isPublic()) {
            $accessModifier = 'public';
        } elseif ($node->isProtected()) {
            $accessModifier = 'protected';
        } elseif ($node->isPrivate()) {
            $accessModifier = 'private';
        }

        $function = new Structures\Function_(
            $node->name->name,
            null,
            $this->file,
            $node->getLine(),
            $node->getAttribute('endLine'),
            false,
            $documentation['deprecated'],
            $documentation['descriptions']['short'],
            $documentation['descriptions']['long'],
            $documentation['return']['description'],
            $localType,
            $this->structure,
            $accessModifier ? $accessModifierMap[$accessModifier] : null,
            false,
            $node->isStatic(),
            $node->isAbstract(),
            $node->isFinal(),
            !empty($docComment),
            $throws,
            [],
            $returnTypes
        );

        $this->storage->persist($function);

        $parameters = [];

        foreach ($node->getParams() as $param) {
            $localType = null;
            $resolvedType = null;

            $typeNode = $param->type;

            if ($typeNode instanceof Node\NullableType) {
                $typeNode = $typeNode->type;
            }

            if ($typeNode instanceof Node\Name) {
                $localType = NodeHelpers::fetchClassName($typeNode);
                $resolvedType = NodeHelpers::fetchClassName($typeNode->getAttribute('resolvedName'));
            } elseif ($typeNode instanceof Node\Identifier) {
                $localType = $typeNode->name;
                $resolvedType = $typeNode->name;
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
            }

            $parameter = new Structures\FunctionParameter(
                $function,
                $param->var->name,
                $localType,
                $types,
                $parameterDoc ? $parameterDoc['description'] : null,
                $defaultValue,
                $isNullable,
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
    protected function parseClassConstantStatementNode(Node\Stmt\ClassConst $node): void
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
    protected function parseClassConstantNode(Node\Const_ $node, Node\Stmt\ClassConst $classConst): void
    {
        $filePosition = new FilePosition($this->filePath, new Position($node->getLine(), 0));

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
            $typeList = $this->nodeTypeDeducer->deduce(
                $node->value,
                $this->filePath,
                $defaultValue,
                0
            );

            $types = array_map(function (string $type) {
                return new Structures\TypeInfo($type, $type);
            }, $typeList);
        }

        $accessModifierMap = $this->getAccessModifierMap();

        $accessModifier = null;

        if ($classConst->isPublic()) {
            $accessModifier = 'public';
        } elseif ($classConst->isProtected()) {
            $accessModifier = 'protected';
        } elseif ($classConst->isPrivate()) {
            $accessModifier = 'private';
        }

        $constant = new Structures\Constant(
            $node->name->name,
            null,
            $this->file,
            $node->getLine(),
            $node->getAttribute('endLine'),
            $defaultValue,
            $documentation['deprecated'] ? 1 : 0,
            false,
            !empty($docComment),
            $shortDescription,
            $documentation['descriptions']['long'],
            $varDocumentation ? $varDocumentation['description'] : null,
            $types,
            $this->structure,
            $accessModifier ? $accessModifierMap[$accessModifier] : null
        );

        $this->storage->persist($constant);
    }

    /**
     * @param array        $rawData
     * @param int          $file
     * @param int          $structure
     * @param int          $amId
     * @param FilePosition $filePosition
     *
     * @return void
     */
    protected function indexMagicProperty(
        array $rawData,
        int $file,
        int $structure,
        int $amId,
        FilePosition $filePosition
    ): void {
        $types = [];

        if ($rawData['type']) {
            $types = $this->getTypeDataForTypeSpecification($rawData['type'], $filePosition);
        }

        $propertyId = $this->storage->insert(IndexStorageItemEnum::PROPERTIES, [
            'name'                  => $rawData['name'],
            'file_id'               => $file,
            'start_line'            => $filePosition->getPosition()->getLine(),
            'end_line'              => $filePosition->getPosition()->getLine(),
            'default_value'         => null,
            'is_deprecated'         => 0,
            'is_magic'              => 1,
            'is_static'             => $rawData['isStatic'] ? 1 : 0,
            'has_docblock'          => 0,
            'short_description'     => $rawData['description'],
            'long_description'      => null,
            'type_description'      => null,
            'structure_id'          => $structure,
            'access_modifier_id'    => $amId,
            'types_serialized'      => serialize($types)
        ]);
    }

    /**
     * @param array        $rawData
     * @param int|null     $structure
     * @param int|null     $amId
     * @param FilePosition $filePosition
     *
     * @return void
     */
    protected function indexMagicMethod(
        array $rawData,
        ?int $structure,
        ?int $amId,
        FilePosition $filePosition
    ): void {
        $returnTypes = [];

        if ($rawData['type']) {
            $returnTypes = $this->getTypeDataForTypeSpecification($rawData['type'], $filePosition);
        }

        $function = new Structures\Function_(
            $rawData['name'],
            null,
            $this->file,
            $filePosition->getPosition()->getLine(),
            $filePosition->getPosition()->getLine(),
            false,
            false,
            $rawData['description'],
            null,
            null,
            null,
            $structure,
            $amId,
            true,
            $rawData['isStatic'],
            false,
            false,
            false,
            [],
            [],
            $returnTypes
        );

        $this->storage->persist($function);

        foreach ($rawData['requiredParameters'] as $parameterName => $parameter) {
            $types = [];

            if ($parameter['type']) {
                $types = $this->getTypeDataForTypeSpecification($parameter['type'], $filePosition);
            }

            $parameter = new Structures\FunctionParameter(
                $function,
                mb_substr($parameterName, 1),
                null,
                $types,
                null,
                null,
                false,
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

            $parameter = new Structures\FunctionParameter(
                $function,
                mb_substr($parameterName, 1),
                null,
                $types,
                null,
                null,
                false,
                false,
                true,
                false
            );

            $this->storage->persist($parameter);
        }
    }

    /**
     * @param string       $typeSpecification
     * @param FilePosition $filePosition
     *
     * @return array[]
     */
    protected function getTypeDataForTypeSpecification(string $typeSpecification, FilePosition $filePosition): array
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
    protected function getTypeDataForTypeList(array $typeList, FilePosition $filePosition): array
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
    protected function getAccessModifierMap(): array
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
     * @return array
     */
    protected function getStructureTypeMap(): array
    {
        if (!$this->structureTypeMap) {
            $types = $this->storage->getStructureTypes();

            $this->structureTypeMap = [];

            foreach ($types as $type) {
                $this->structureTypeMap[$type->getName()] = $type;
            }
        }

        return $this->structureTypeMap;
    }
}
