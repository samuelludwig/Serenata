<?php

namespace PhpIntegrator\Indexing\Visiting;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Common\Position;
use PhpIntegrator\Common\FilePosition;

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
     * @var int
     */
    private $fileId;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var int
     */
    private $seId;

    /**
     * @param StorageInterface                           $storage
     * @param TypeAnalyzer                               $typeAnalyzer
     * @param DocblockParser                             $docblockParser
     * @param NodeTypeDeducerInterface                   $nodeTypeDeducer
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param int                                        $fileId
     * @param string                                     $code
     * @param string                                     $filePath
     */
    public function __construct(
        StorageInterface $storage,
        TypeAnalyzer $typeAnalyzer,
        DocblockParser $docblockParser,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        int $fileId,
        string $code,
        string $filePath
    ) {
        $this->storage = $storage;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockParser = $docblockParser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->fileId = $fileId;
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
        ], $node->name);

        if ($node instanceof Node\Stmt\Class_) {
            $structureTypeId = $structureTypeMap['class'];
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $structureTypeId = $structureTypeMap['interface'];
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $structureTypeId = $structureTypeMap['trait'];
        }

        $seData = [
            'name'              => $node->name,
            'fqcn'              => '\\' . $node->namespacedName->toString(),
            'file_id'           => $this->fileId,
            'start_line'        => $node->getLine(),
            'end_line'          => $node->getAttribute('endLine'),
            'structure_type_id' => $structureTypeId,
            'is_abstract'       => false,
            'is_final'          => false,
            'is_deprecated'     => $documentation['deprecated'] ? 1 : 0,
            'is_annotation'     => $documentation['annotation'] ? 1 : 0,
            'is_builtin'        => 0,
            'has_docblock'      => empty($docComment) ? 0 : 1,
            'short_description' => $documentation['descriptions']['short'],
            'long_description'  => $documentation['descriptions']['long']
        ];

        if ($node instanceof Node\Stmt\Class_) {
            $seData['is_abstract'] = $node->isAbstract() ? 1 : 0;
            $seData['is_final'] = $node->isFinal() ? 1 : 0;
        }

        $seId = $this->storage->insertStructure($seData);

        $accessModifierMap = $this->getAccessModifierMap();

        if ($node instanceof Node\Stmt\Class_) {
            if ($node->extends) {
                $parent = NodeHelpers::fetchClassName($node->extends->getAttribute('resolvedName'));

                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_PARENTS_LINKED, [
                    'structure_id'          => $seId,
                    'linked_structure_fqcn' => $this->typeAnalyzer->getNormalizedFqcn($parent)
                ]);
            }

            foreach ($node->implements as $implementedName) {
                $resolvedName = NodeHelpers::fetchClassName($implementedName->getAttribute('resolvedName'));

                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_INTERFACES_LINKED, [
                    'structure_id'          => $seId,
                    'linked_structure_fqcn' => $this->typeAnalyzer->getNormalizedFqcn($resolvedName)
                ]);
            }
        } elseif ($node instanceof Node\Stmt\Interface_) {
            foreach ($node->extends as $extends) {
                $parent = NodeHelpers::fetchClassName($extends->getAttribute('resolvedName'));

                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_PARENTS_LINKED, [
                    'structure_id'          => $seId,
                    'linked_structure_fqcn' => $this->typeAnalyzer->getNormalizedFqcn($parent)
                ]);
            }
        }

        // Index magic properties.
        $magicProperties = array_merge(
            $documentation['properties'],
            $documentation['propertiesReadOnly'],
            $documentation['propertiesWriteOnly']
        );

        $filePosition = new FilePosition($this->filePath, new Position($node->getLine(), 0));

        $positionalNameResolver = $this->structureAwareNameResolverFactory->create($filePosition);

        foreach ($magicProperties as $propertyName => $propertyData) {
            // Use the same line as the class definition, it matters for e.g. type resolution.
            $propertyData['name'] = mb_substr($propertyName, 1);

            $this->indexMagicProperty(
                $propertyData,
                $this->fileId,
                $seId,
                $accessModifierMap['public'],
                $positionalNameResolver,
                $filePosition
            );
        }

        // Index magic methods.
        foreach ($documentation['methods'] as $methodName => $methodData) {
            // Use the same line as the class definition, it matters for e.g. type resolution.
            $methodData['name'] = $methodName;

            $this->indexMagicMethod(
                $methodData,
                $this->fileId,
                $seId,
                $accessModifierMap['public'],
                $positionalNameResolver,
                $filePosition
            );
        }

        $this->seId = $seId;
    }

    /**
     * @param Node\Stmt\TraitUse $node
     *
     * @return void
     */
    protected function parseTraitUseNode(Node\Stmt\TraitUse $node): void
    {
        foreach ($node->traits as $traitName) {
            $trait = NodeHelpers::fetchClassName($traitName->getAttribute('resolvedName'));

            $this->storage->insert(IndexStorageItemEnum::STRUCTURES_TRAITS_LINKED, [
                'structure_id'          => $this->seId,
                'linked_structure_fqcn' => $this->typeAnalyzer->getNormalizedFqcn($trait)
            ]);
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
                    'structure_id'         => $this->seId,
                    'trait_structure_fqcn' => ($trait !== null) ?
                        $this->typeAnalyzer->getNormalizedFqcn($trait) : null,
                    'access_modifier_id'   => $accessModifier ? $accessModifierMap[$accessModifier] : null,
                    'name'                 => $adaptation->method,
                    'alias'                => $adaptation->newName
                ]);
            } elseif ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
                $fqcn = NodeHelpers::fetchClassName($adaptation->trait->getAttribute('resolvedName'));

                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_TRAITS_PRECEDENCES, [
                    'structure_id'         => $this->seId,
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

        $positionalNameResolver = $this->structureAwareNameResolverFactory->create($filePosition);

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

                $types = $this->getTypeDataForTypeSpecification(
                    $varDocumentation['type'],
                    $filePosition,
                    $positionalNameResolver
                );
            } elseif ($property->default) {
                $typeList = $this->nodeTypeDeducer->deduce(
                    $property->default,
                    $this->filePath,
                    $defaultValue,
                    0
                );

                $types = array_map(function (string $type) {
                    return [
                        'type' => $type,
                        'fqcn' => $type
                    ];
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

            $propertyId = $this->storage->insert(IndexStorageItemEnum::PROPERTIES, [
                'name'                  => $property->name,
                'file_id'               => $this->fileId,
                'start_line'            => $property->getLine(),
                'end_line'              => $property->getAttribute('endLine'),
                'default_value'         => $defaultValue,
                'is_deprecated'         => $documentation['deprecated'] ? 1 : 0,
                'is_magic'              => 0,
                'is_static'             => $node->isStatic() ? 1 : 0,
                'has_docblock'          => empty($docComment) ? 0 : 1,
                'short_description'     => $shortDescription,
                'long_description'      => $documentation['descriptions']['long'],
                'type_description'      => $varDocumentation ? $varDocumentation['description'] : null,
                'structure_id'          => $this->seId,
                'access_modifier_id'    => $accessModifier ? $accessModifierMap[$accessModifier] : null,
                'types_serialized'      => serialize($types)
            ]);
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
        } elseif (is_string($nodeType)) {
            $localType = (string) $nodeType;
            $resolvedType = (string) $nodeType;
        }

        $filePosition = new FilePosition($this->filePath, new Position($node->getLine(), 0));

        $positionalNameResolver = $this->structureAwareNameResolverFactory->create($filePosition);

        $isReturnTypeNullable = ($node->getReturnType() instanceof Node\NullableType);
        $docComment = $node->getDocComment() ? $node->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::THROWS,
            DocblockParser::PARAM_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION,
            DocblockParser::RETURN_VALUE
        ], $node->name);

        $returnTypes = [];

        if ($documentation && $documentation['return']['type']) {
            $returnTypes = $this->getTypeDataForTypeSpecification(
                $documentation['return']['type'],
                $filePosition,
                $positionalNameResolver
            );
        } elseif ($localType) {
            $returnTypes = [
                [
                    'type' => $localType,
                    'fqcn' => $resolvedType ?: $localType
                ]
            ];

            if ($isReturnTypeNullable) {
                $returnTypes[] = ['type' => 'null', 'fqcn' => 'null'];
            }
        }

        $throws = [];

        foreach ($documentation['throws'] as $throw) {
            $typeData = $this->getTypeDataForTypeSpecification($throw['type'], $filePosition, $positionalNameResolver);
            $typeData = array_shift($typeData);

            $throwsData = [
                'type'        => $typeData['type'],
                'full_type'   => $typeData['fqcn'],
                'description' => $throw['description'] ?: null
            ];

            $throws[] = $throwsData;
        }

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
            } elseif (is_string($typeNode)) {
                $localType = (string) $typeNode;
                $resolvedType = (string) $typeNode;
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

            $parameterKey = '$' . $param->name;
            $parameterDoc = isset($documentation['params'][$parameterKey]) ?
                $documentation['params'][$parameterKey] : null;

            $types = [];

            if ($parameterDoc) {
                $types = $this->getTypeDataForTypeSpecification(
                    $parameterDoc['type'],
                    $filePosition,
                    $positionalNameResolver
                );
            } elseif ($localType) {
                $parameterType = $localType;
                $parameterFullType = $resolvedType ?: $parameterType;

                if ($param->variadic) {
                    $parameterType .= '[]';
                    $parameterFullType .= '[]';
                }

                $types = [
                    [
                        'type' => $parameterType,
                        'fqcn' => $parameterFullType
                    ]
                ];

                if ($isNullable) {
                    $types[] = [
                        'type' => 'null',
                        'fqcn' => 'null'
                    ];
                }
            }

            $parameters[] = [
                'name'             => $param->name,
                'type_hint'        => $localType,
                'types_serialized' => serialize($types),
                'description'      => $parameterDoc ? $parameterDoc['description'] : null,
                'default_value'    => $defaultValue,
                'is_nullable'      => $isNullable ? 1 : 0,
                'is_reference'     => $param->byRef ? 1 : 0,
                'is_optional'      => $param->default ? 1 : 0,
                'is_variadic'      => $param->variadic ? 1 : 0
            ];
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

        $functionId = $this->storage->insert(IndexStorageItemEnum::FUNCTIONS, [
            'name'                    => $node->name,
            'fqcn'                    => null,
            'file_id'                 => $this->fileId,
            'start_line'              => $node->getLine(),
            'end_line'                => $node->getAttribute('endLine'),
            'is_builtin'              => 0,
            'is_abstract'             => $node->isAbstract() ? 1 : 0,
            'is_final'                => $node->isFinal() ? 1 : 0,
            'is_deprecated'           => $documentation['deprecated'] ? 1 : 0,
            'short_description'       => $documentation['descriptions']['short'],
            'long_description'        => $documentation['descriptions']['long'],
            'return_description'      => $documentation['return']['description'],
            'return_type_hint'        => $localType,
            'structure_id'            => $this->seId,
            'access_modifier_id'      => $accessModifier ? $accessModifierMap[$accessModifier] : null,
            'is_magic'                => 0,
            'is_static'               => $node->isStatic() ? 1 : 0,
            'has_docblock'            => empty($docComment) ? 0 : 1,
            'throws_serialized'       => serialize($throws),
            'parameters_serialized'   => serialize($parameters),
            'return_types_serialized' => serialize($returnTypes)
        ]);

        foreach ($parameters as $parameter) {
            $parameter['function_id'] = $functionId;

            $this->storage->insert(IndexStorageItemEnum::FUNCTIONS_PARAMETERS, $parameter);
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

        $positionalNameResolver = $this->structureAwareNameResolverFactory->create($filePosition);

        $docComment = $classConst->getDocComment() ? $classConst->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::VAR_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION
        ], $node->name);

        $varDocumentation = isset($documentation['var']['$' . $node->name]) ?
            $documentation['var']['$' . $node->name] :
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

            $types = $this->getTypeDataForTypeSpecification(
                $varDocumentation['type'],
                $filePosition,
                $positionalNameResolver
            );
        } elseif ($node->value) {
            $typeList = $this->nodeTypeDeducer->deduce(
                $node->value,
                $this->filePath,
                $defaultValue,
                0
            );

            $types = array_map(function (string $type) {
                return [
                    'type' => $type,
                    'fqcn' => $type
                ];
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

        $this->storage->insert(IndexStorageItemEnum::CONSTANTS, [
            'name'                  => $node->name,
            'fqcn'                  => null,
            'file_id'               => $this->fileId,
            'start_line'            => $node->getLine(),
            'end_line'              => $node->getAttribute('endLine'),
            'default_value'         => $defaultValue,
            'is_builtin'            => 0,
            'is_deprecated'         => $documentation['deprecated'] ? 1 : 0,
            'has_docblock'          => empty($docComment) ? 0 : 1,
            'short_description'     => $shortDescription,
            'long_description'      => $documentation['descriptions']['long'],
            'type_description'      => $varDocumentation ? $varDocumentation['description'] : null,
            'types_serialized'      => serialize($types),
            'structure_id'          => $this->seId,
            'access_modifier_id'    => $accessModifier ? $accessModifierMap[$accessModifier] : null
        ]);
    }

    /**
     * @param array                           $rawData
     * @param int                             $fileId
     * @param int                             $seId
     * @param int                             $amId
     * @param PositionalNameResolverInterface $positionalNameResolver
     * @param FilePosition                    $filePosition
     *
     * @return void
     */
    protected function indexMagicProperty(
        array $rawData,
        int $fileId,
        int $seId,
        int $amId,
        PositionalNameResolverInterface $positionalNameResolver,
        FilePosition $filePosition
    ): void {
        $types = [];

        if ($rawData['type']) {
            $types = $this->getTypeDataForTypeSpecification(
                $rawData['type'],
                $filePosition,
                $positionalNameResolver
            );
        }

        $propertyId = $this->storage->insert(IndexStorageItemEnum::PROPERTIES, [
            'name'                  => $rawData['name'],
            'file_id'               => $fileId,
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
            'structure_id'          => $seId,
            'access_modifier_id'    => $amId,
            'types_serialized'      => serialize($types)
        ]);
    }

    /**
     * @param array                           $rawData
     * @param int                             $fileId
     * @param int|null                        $seId
     * @param int|null                        $amId
     * @param PositionalNameResolverInterface $positionalNameResolver
     * @param FilePosition                    $filePosition
     *
     * @return void
     */
    protected function indexMagicMethod(
        array $rawData,
        int $fileId,
        ?int $seId,
        ?int $amId,
        PositionalNameResolverInterface $positionalNameResolver,
        FilePosition $filePosition
    ): void {
        $returnTypes = [];

        if ($rawData['type']) {
            $returnTypes = $this->getTypeDataForTypeSpecification(
                $rawData['type'],
                $filePosition,
                $positionalNameResolver
            );
        }

        $parameters = [];

        foreach ($rawData['requiredParameters'] as $parameterName => $parameter) {
            $types = [];

            if ($parameter['type']) {
                $types = $this->getTypeDataForTypeSpecification(
                    $parameter['type'],
                    $filePosition,
                    $positionalNameResolver
                );
            }

            $parameters[] = [
                'name'             => mb_substr($parameterName, 1),
                'type_hint'        => null,
                'types_serialized' => serialize($types),
                'description'      => null,
                'default_value'    => null,
                'is_nullable'      => 0,
                'is_reference'     => 0,
                'is_optional'      => 0,
                'is_variadic'      => 0
            ];
        }

        foreach ($rawData['optionalParameters'] as $parameterName => $parameter) {
            $types = [];

            if ($parameter['type']) {
                $types = $this->getTypeDataForTypeSpecification(
                    $parameter['type'],
                    $filePosition,
                    $positionalNameResolver
                );
            }

            $parameters[] = [
                'name'             => mb_substr($parameterName, 1),
                'type_hint'        => null,
                'types_serialized' => serialize($types),
                'description'      => null,
                'default_value'    => null,
                'is_nullable'      => 0,
                'is_reference'     => 0,
                'is_optional'      => 1,
                'is_variadic'      => 0,
            ];
        }

        $functionId = $this->storage->insert(IndexStorageItemEnum::FUNCTIONS, [
            'name'                    => $rawData['name'],
            'fqcn'                    => null,
            'file_id'                 => $fileId,
            'start_line'              => $filePosition->getPosition()->getLine(),
            'end_line'                => $filePosition->getPosition()->getLine(),
            'is_builtin'              => 0,
            'is_abstract'             => 0,
            'is_deprecated'           => 0,
            'short_description'       => $rawData['description'],
            'long_description'        => null,
            'return_description'      => null,
            'return_type_hint'        => null,
            'structure_id'            => $seId,
            'access_modifier_id'      => $amId,
            'is_magic'                => 1,
            'is_static'               => $rawData['isStatic'] ? 1 : 0,
            'has_docblock'            => 0,
            'throws_serialized'       => serialize([]),
            'parameters_serialized'   => serialize($parameters),
            'return_types_serialized' => serialize($returnTypes)
        ]);

        foreach ($parameters as $parameter) {
            $parameter['function_id'] = $functionId;

            $this->storage->insert(IndexStorageItemEnum::FUNCTIONS_PARAMETERS, $parameter);
        }
    }

    /**
     * @param string                          $typeSpecification
     * @param FilePosition                    $filePosition
     * @param PositionalNameResolverInterface $positionalNameResolver
     *
     * @return array[]
     */
    protected function getTypeDataForTypeSpecification(
        string $typeSpecification,
        FilePosition $filePosition,
        PositionalNameResolverInterface $positionalNameResolver
    ): array {
        $typeList = $this->typeAnalyzer->getTypesForTypeSpecification($typeSpecification);

        return $this->getTypeDataForTypeList($typeList, $filePosition, $positionalNameResolver);
    }

    /**
     * @param string[]                        $typeList
     * @param FilePosition                    $filePosition
     * @param PositionalNameResolverInterface $positionalNameResolver
     *
     * @return array[]
     */
    protected function getTypeDataForTypeList(
        array $typeList,
        FilePosition $filePosition,
        PositionalNameResolverInterface $positionalNameResolver
    ): array {
        $types = [];

        foreach ($typeList as $type) {
            $types[] = [
                'type' => $type,
                'fqcn' => $positionalNameResolver->resolve($type, $filePosition)
            ];
        }

        return $types;
    }

    /**
     * @return array
     */
    protected function getAccessModifierMap(): array
    {
        if (!$this->accessModifierMap) {
            $this->accessModifierMap = $this->storage->getAccessModifierMap();
        }

        return $this->accessModifierMap;
    }

    /**
     * @return array
     */
    protected function getStructureTypeMap(): array
    {
        if (!$this->structureTypeMap) {
            $this->structureTypeMap = $this->storage->getStructureTypeMap();
        }

        return $this->structureTypeMap;
    }
}
