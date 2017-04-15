<?php

namespace PhpIntegrator\Indexing;

use UnexpectedValueException;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\TypeNormalizerInterface;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolver;
use PhpIntegrator\Analysis\Typing\Resolving\TypeResolverInterface;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

use PhpIntegrator\Parsing\DocblockParser;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that traverses a set of nodes, indexing the nodes in the process.
 *
 * The outline index only contains "direct" data, meaning data that is directly attached to an element. For example,
 * classes will only have their direct members attached in the index. The index will also keep track of links between
 * structural elements and parents, implemented interfaces, and more, but it will not duplicate data, meaning parent
 * methods will not be copied and attached to child classes.
 *
 * The index keeps track of 'outlines' that are confined to a single file. It in itself does not do anything
 * "intelligent" such as automatically inheriting docblocks from overridden methods.
 */
final class OutlineIndexingVisitor extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    private $structures = [];

    /**
     * @var array
     */
    private $globalFunctions = [];

    /**
     * @var array
     */
    private $globalConstants = [];

    /**
     * @var array
     */
    private $globalDefines = [];

    /**
     * @var Node\Stmt\Class_|null
     */
    private $currentStructure;

    /**
     * @var TypeNormalizerInterface
     */
    private $typeNormalizer;

    /**
     * @var string
     */
    private $code;

    /**
     * The storage to use for index data.
     *
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
     * @var TypeResolverInterface
     */
    private $typeResolver;

    /**
     * @var Parser
     */
    private $parser;

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
    private $filePath;

    /**
     * @param StorageInterface                 $storage
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param TypeResolverInterface            $typeResolver
     * @param DocblockParser                   $docblockParser
     * @param NodeTypeDeducerInterface         $nodeTypeDeducer
     * @param Parser                           $parser
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param TypeNormalizerInterface          $typeNormalizer
     * @param int                              $fileId
     * @param string                           $code
     * @param string                           $filePath
     */
    public function __construct(
        StorageInterface $storage,
        TypeAnalyzer $typeAnalyzer,
        TypeResolverInterface $typeResolver,
        DocblockParser $docblockParser,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        Parser $parser,
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        TypeNormalizerInterface $typeNormalizer,
        int $fileId,
        string $code,
        string $filePath
    ) {
        $this->storage = $storage;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->typeResolver = $typeResolver;
        $this->docblockParser = $docblockParser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->parser = $parser;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->typeNormalizer = $typeNormalizer;
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
            $this->parseClassConstantNode($node);
        } elseif ($node instanceof Node\Stmt\Function_) {
            $this->parseFunctionNode($node);
        } elseif ($node instanceof Node\Stmt\Const_) {
            $this->parseConstantNode($node);
        } elseif ($node instanceof Node\Stmt\Class_) {
            if ($node->isAnonymous()) {
                // Ticket #45 - Skip PHP 7 anonymous classes.
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            $this->parseClassNode($node);
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $this->parseInterfaceNode($node);
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->parseTraitNode($node);
        } elseif ($node instanceof Node\Stmt\TraitUse) {
            $this->parseTraitUseNode($node);
        } elseif (
            $node instanceof Node\Expr\FuncCall &&
            $node->name instanceof Node\Name &&
            $node->name->toString() === 'define'
        ) {
            $this->parseDefineNode($node);
        }
    }

    /**
     * @param Node\Stmt\Class_ $node
     *
     * @return void
     */
    protected function parseClassNode(Node\Stmt\Class_ $node): void
    {
        $this->currentStructure = $node;

        $interfaces = [];

        foreach ($node->implements as $implementedName) {
            $interfaces[] = NodeHelpers::fetchClassName($implementedName->getAttribute('resolvedName'));
        }

        $fqcn = $this->typeNormalizer->getNormalizedFqcn($node->namespacedName->toString());

        $this->structures[$fqcn] = [
            'name'           => $node->name,
            'fqcn'           => $fqcn,
            'type'           => 'class',
            'startLine'      => $node->getLine(),
            'endLine'        => $node->getAttribute('endLine'),
            'startPosName'   => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos') : null,
            'endPosName'     => $node->getAttribute('startFilePos') ? ($node->getAttribute('startFilePos') + 1) : null,
            'isAbstract'     => $node->isAbstract(),
            'isFinal'        => $node->isFinal(),
            'docComment'     => $node->getDocComment() ? $node->getDocComment()->getText() : null,
            'parents'        => $node->extends ? [NodeHelpers::fetchClassName($node->extends->getAttribute('resolvedName'))] : [],
            'interfaces'     => $interfaces,
            'traits'         => [],
            'methods'        => [],
            'properties'     => [],
            'constants'      => []
        ];
    }

    /**
     * @param Node\Stmt\Interface_ $node
     *
     * @return void
     */
    protected function parseInterfaceNode(Node\Stmt\Interface_ $node): void
    {
        if (!isset($node->namespacedName)) {
            return;
        }

        $this->currentStructure = $node;

        $extendedInterfaces = [];

        foreach ($node->extends as $extends) {
            $extendedInterfaces[] = NodeHelpers::fetchClassName($extends->getAttribute('resolvedName'));
        }

        $fqcn = $this->typeNormalizer->getNormalizedFqcn($node->namespacedName->toString());

        $this->structures[$fqcn] = [
            'name'           => $node->name,
            'fqcn'           => $fqcn,
            'type'           => 'interface',
            'startLine'      => $node->getLine(),
            'endLine'        => $node->getAttribute('endLine'),
            'startPosName'   => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos') : null,
            'endPosName'     => $node->getAttribute('startFilePos') ? ($node->getAttribute('startFilePos') + 1) : null,
            'parents'        => $extendedInterfaces,
            'docComment'     => $node->getDocComment() ? $node->getDocComment()->getText() : null,
            'traits'         => [],
            'methods'        => [],
            'properties'     => [],
            'constants'      => []
        ];
    }

    /**
     * @param Node\Stmt\Trait_ $node
     *
     * @return void
     */
    protected function parseTraitNode(Node\Stmt\Trait_ $node): void
    {
        if (!isset($node->namespacedName)) {
            return;
        }

        $this->currentStructure = $node;

        $fqcn = $this->typeNormalizer->getNormalizedFqcn($node->namespacedName->toString());

        $this->structures[$fqcn] = [
            'name'           => $node->name,
            'fqcn'           => $fqcn,
            'type'           => 'trait',
            'startLine'      => $node->getLine(),
            'endLine'        => $node->getAttribute('endLine'),
            'startPosName'   => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos') : null,
            'endPosName'     => $node->getAttribute('startFilePos') ? ($node->getAttribute('startFilePos') + 1) : null,
            'docComment'     => $node->getDocComment() ? $node->getDocComment()->getText() : null,
            'methods'        => [],
            'properties'     => [],
            'constants'      => []
        ];
    }

    /**
     * @param Node\Stmt\TraitUse $node
     *
     * @return void
     */
    protected function parseTraitUseNode(Node\Stmt\TraitUse $node): void
    {
        $fqcn = $this->typeNormalizer->getNormalizedFqcn($this->currentStructure->namespacedName->toString());

        foreach ($node->traits as $traitName) {
            $this->structures[$fqcn]['traits'][] =
                NodeHelpers::fetchClassName($traitName->getAttribute('resolvedName'));
        }

        foreach ($node->adaptations as $adaptation) {
            if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
                $this->structures[$fqcn]['traitAliases'][] = [
                    'name'                       => $adaptation->method,
                    'alias'                      => $adaptation->newName,
                    'trait'                      => $adaptation->trait ? NodeHelpers::fetchClassName($adaptation->trait->getAttribute('resolvedName')) : null,
                    'isPublic'                   => ($adaptation->newModifier === 1),
                    'isPrivate'                  => ($adaptation->newModifier === 4),
                    'isProtected'                => ($adaptation->newModifier === 2),
                    'isInheritingAccessModifier' => ($adaptation->newModifier === null)
                ];
            } elseif ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
                $this->structures[$fqcn]['traitPrecedences'][] = [
                    'name'  => $adaptation->method,
                    'trait' => NodeHelpers::fetchClassName($adaptation->trait->getAttribute('resolvedName'))
                ];
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
        $fqcn = $this->typeNormalizer->getNormalizedFqcn($this->currentStructure->namespacedName->toString());

        foreach ($node->props as $property) {
            $this->structures[$fqcn]['properties'][$property->name] = [
                'name'             => $property->name,
                'startLine'        => $property->getLine(),
                'endLine'          => $property->getAttribute('endLine'),
                'startPosName'     => $property->getAttribute('startFilePos') ? $property->getAttribute('startFilePos') : null,
                'endPosName'       => $property->getAttribute('startFilePos') ? ($property->getAttribute('startFilePos') + mb_strlen($property->name) + 1) : null,
                'isPublic'         => $node->isPublic(),
                'isPrivate'        => $node->isPrivate(),
                'isStatic'         => $node->isStatic(),
                'isProtected'      => $node->isProtected(),
                'docComment'       => $node->getDocComment() ? $node->getDocComment()->getText() : null,
                'defaultValueNode' => $property->default,

                'defaultValue' => $property->default ?
                    substr(
                        $this->code,
                        $property->default->getAttribute('startFilePos'),
                        $property->default->getAttribute('endFilePos') - $property->default->getAttribute('startFilePos') + 1
                    ) :
                    null
            ];
        }
    }

    /**
     * @param Node\Stmt\Function_ $node
     *
     * @return void
     */
    protected function parseFunctionNode(Node\Stmt\Function_ $node): void
    {
        $data = $this->extractFunctionLikeNodeData($node);

        $fqcn = $this->typeNormalizer->getNormalizedFqcn(
            isset($node->namespacedName) ? $node->namespacedName->toString() : $node->name
        );

        $this->globalFunctions[$fqcn] = $data + [
            'fqcn' => $fqcn
        ];
    }

    /**
     * @param Node\Stmt\ClassMethod $node
     *
     * @return void
     */
    protected function parseClassMethodNode(Node\Stmt\ClassMethod $node): void
    {
        $fqcn = $this->typeNormalizer->getNormalizedFqcn($this->currentStructure->namespacedName->toString());

        $this->structures[$fqcn]['methods'][$node->name] = $this->extractFunctionLikeNodeData($node) + [
            'isPublic'       => $node->isPublic(),
            'isPrivate'      => $node->isPrivate(),
            'isProtected'    => $node->isProtected(),
            'isAbstract'     => $node->isAbstract(),
            'isFinal'        => $node->isFinal(),
            'isStatic'       => $node->isStatic()
        ];
    }

    /**
     * @param Node\FunctionLike $node
     *
     * @return array
     */
    protected function extractFunctionLikeNodeData(Node\FunctionLike $node): array
    {
        $parameters = [];

        foreach ($node->getParams() as $i => $param) {
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

            $parameters[$i] = [
                'name'             => $param->name,
                'type'             => $localType,
                'fullType'         => $resolvedType,
                'isReference'      => $param->byRef,
                'isVariadic'       => $param->variadic,
                'isOptional'       => $param->default ? true : false,
                'defaultValueNode' => $param->default,

                'isNullable'   => (
                    ($param->type instanceof Node\NullableType) ||
                    ($param->default instanceof Node\Expr\ConstFetch && $param->default->name->toString() === 'null')
                ),

                'defaultValue' => $param->default ?
                    substr(
                        $this->code,
                        $param->default->getAttribute('startFilePos'),
                        $param->default->getAttribute('endFilePos') - $param->default->getAttribute('startFilePos') + 1
                    ) :
                    null
            ];
        }

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

        return [
            'name'                 => $node->name,
            'startLine'            => $node->getLine(),
            'endLine'              => $node->getAttribute('endLine'),
            'startPosName'         => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos') : null,
            'endPosName'           => $node->getAttribute('startFilePos') ? ($node->getAttribute('startFilePos') + 1) : null,
            'returnType'           => $localType,
            'fullReturnType'       => $resolvedType,
            'isReturnTypeNullable' => ($node->getReturnType() instanceof Node\NullableType),
            'parameters'           => $parameters,
            'docComment'           => $node->getDocComment() ? $node->getDocComment()->getText() : null
        ];
    }

    /**
     * @param Node\Stmt\ClassConst $node
     *
     * @return void
     */
    protected function parseClassConstantNode(Node\Stmt\ClassConst $node): void
    {
        $fqcn = $this->typeNormalizer->getNormalizedFqcn($this->currentStructure->namespacedName->toString());

        foreach ($node->consts as $const) {
            $this->structures[$fqcn]['constants'][$const->name] = [
                'name'             => $const->name,
                'startLine'        => $const->getLine(),
                'endLine'          => $const->getAttribute('endLine'),
                'startPosName'     => $const->getAttribute('startFilePos') ? $const->getAttribute('startFilePos') : null,
                'endPosName'       => $const->getAttribute('startFilePos') ? ($const->getAttribute('startFilePos') + mb_strlen($const->name)) : null,
                'docComment'       => $node->getDocComment() ? $node->getDocComment()->getText() : null,
                'isPublic'         => $node->isPublic(),
                'isPrivate'        => $node->isPrivate(),
                'isProtected'      => $node->isProtected(),
                'defaultValueNode' => $const->value,

                'defaultValue' => substr(
                    $this->code,
                    $const->value->getAttribute('startFilePos'),
                    $const->value->getAttribute('endFilePos') - $const->value->getAttribute('startFilePos') + 1
                )
            ];
        }
    }

    /**
     * @param Node\Stmt\Const_ $node
     *
     * @return void
     */
    protected function parseConstantNode(Node\Stmt\Const_ $node): void
    {
        foreach ($node->consts as $const) {
            $fqcn = $this->typeNormalizer->getNormalizedFqcn(
                isset($const->namespacedName) ? $const->namespacedName->toString() : $const->name
            );

            $this->globalConstants[$fqcn] = [
                'name'             => $const->name,
                'fqcn'             => $fqcn,
                'startLine'        => $const->getLine(),
                'endLine'          => $const->getAttribute('endLine'),
                'startPosName'     => $const->getAttribute('startFilePos') ? $const->getAttribute('startFilePos') : null,
                'endPosName'       => $const->getAttribute('endFilePos') ? $const->getAttribute('endFilePos') : null,
                'docComment'       => $node->getDocComment() ? $node->getDocComment()->getText() : null,
                'defaultValueNode' => $const->value,

                'defaultValue' => substr(
                    $this->code,
                    $const->value->getAttribute('startFilePos'),
                    $const->value->getAttribute('endFilePos') - $const->value->getAttribute('startFilePos') + 1
                )
            ];
        }
    }

    /**
     * @param Node\Expr\FuncCall $node
     *
     * @return void
     */
    protected function parseDefineNode(Node\Expr\FuncCall $node): void
    {
        if (count($node->args) < 2) {
            return;
        }

        $nameValue = $node->args[0]->value;

        if (!$nameValue instanceof Node\Scalar\String_) {
            return;
        }

        // Defines can be namespaced if their name contains slashes, see also
        // https://php.net/manual/en/function.define.php#90282
        $name = new Node\Name((string) $nameValue->value);

        $fqcn = $this->typeNormalizer->getNormalizedFqcn($name->toString());

        $this->globalDefines[$fqcn] = [
            'name'             => $name->getLast(),
            'fqcn'             => $fqcn,
            'startLine'        => $node->getLine(),
            'endLine'          => $node->getAttribute('endLine'),
            'startPosName'     => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos') : null,
            'endPosName'       => $node->getAttribute('endFilePos') ? $node->getAttribute('endFilePos') : null,
            'docComment'       => $node->getDocComment() ? $node->getDocComment()->getText() : null,
            'defaultValueNode' => $node->args[1],

            'defaultValue' => substr(
                $this->code,
                $node->args[1]->getAttribute('startFilePos'),
                $node->args[1]->getAttribute('endFilePos') - $node->args[1]->getAttribute('startFilePos') + 1
            )
        ];
    }

    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
        if ($this->currentStructure === $node) {
            $this->currentStructure = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function afterTraverse(array $nodes)
    {
        $fileTypeResolver = $this->fileTypeResolverFactory->create($this->filePath);

        foreach ($this->structures as $fqcn => $structure) {
             $this->indexStructure(
                 $structure,
                 $this->filePath,
                 $this->fileId,
                 $fqcn,
                 false,
                 $fileTypeResolver
             );
         }

         foreach ($this->globalFunctions as $function) {
             $this->indexFunction($function, $this->fileId, null, null, false, $fileTypeResolver);
         }

         foreach ($this->globalConstants as $constant) {
             $this->indexConstant($constant, $this->filePath, $this->fileId, null, null, $fileTypeResolver);
         }

         foreach ($this->globalDefines as $define) {
             $this->indexConstant($define, $this->filePath, $this->fileId, null, null, $fileTypeResolver);
         }
     }

    /**
     * Indexes the specified structural element.
     *
     * @param array            $rawData
     * @param string           $filePath
     * @param int              $fileId
     * @param string           $fqcn
     * @param bool             $isBuiltin
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return int The ID of the structural element.
     */
    protected function indexStructure(
        array $rawData,
        string $filePath,
        int $fileId,
        string $fqcn,
        bool $isBuiltin,
        FileTypeResolver $fileTypeResolver
    ): int {
        $structureTypeMap = $this->getStructureTypeMap();

        $documentation = $this->docblockParser->parse($rawData['docComment'], [
            DocblockParser::DEPRECATED,
            DocblockParser::ANNOTATION,
            DocblockParser::DESCRIPTION,
            DocblockParser::METHOD,
            DocblockParser::PROPERTY,
            DocblockParser::PROPERTY_READ,
            DocblockParser::PROPERTY_WRITE
        ], $rawData['name']);

        $seData = [
            'name'              => $rawData['name'],
            'fqcn'              => $fqcn,
            'file_id'           => $fileId,
            'start_line'        => $rawData['startLine'],
            'end_line'          => $rawData['endLine'],
            'structure_type_id' => $structureTypeMap[$rawData['type']],
            'is_abstract'       => (isset($rawData['isAbstract']) && $rawData['isAbstract']) ? 1 : 0,
            'is_final'          => (isset($rawData['isFinal']) && $rawData['isFinal']) ? 1 : 0,
            'is_deprecated'     => $documentation['deprecated'] ? 1 : 0,
            'is_annotation'     => $documentation['annotation'] ? 1 : 0,
            'is_builtin'        => $isBuiltin ? 1 : 0,
            'has_docblock'      => empty($rawData['docComment']) ? 0 : 1,
            'short_description' => $documentation['descriptions']['short'],
            'long_description'  => $documentation['descriptions']['long']
        ];

        $seId = $this->storage->insertStructure($seData);

        $accessModifierMap = $this->getAccessModifierMap();

        if (isset($rawData['parents'])) {
            foreach ($rawData['parents'] as $parent) {
                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_PARENTS_LINKED, [
                    'structure_id'          => $seId,
                    'linked_structure_fqcn' => $this->typeAnalyzer->getNormalizedFqcn($parent)
                ]);
            }
        }

        if (isset($rawData['interfaces'])) {
            foreach ($rawData['interfaces'] as $interface) {
                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_INTERFACES_LINKED, [
                    'structure_id'          => $seId,
                    'linked_structure_fqcn' => $this->typeAnalyzer->getNormalizedFqcn($interface)
                ]);
            }
        }

        if (isset($rawData['traits'])) {
            foreach ($rawData['traits'] as $trait) {
                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_TRAITS_LINKED, [
                    'structure_id'          => $seId,
                    'linked_structure_fqcn' => $this->typeAnalyzer->getNormalizedFqcn($trait)
                ]);
            }
        }

        if (isset($rawData['traitAliases'])) {
            foreach ($rawData['traitAliases'] as $traitAlias) {
                $accessModifier = $this->parseAccessModifier($traitAlias, true);

                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_TRAITS_ALIASES, [
                    'structure_id'         => $seId,
                    'trait_structure_fqcn' => ($traitAlias['trait'] !== null) ?
                        $this->typeAnalyzer->getNormalizedFqcn($traitAlias['trait']) : null,
                    'access_modifier_id'   => $accessModifier ? $accessModifierMap[$accessModifier] : null,
                    'name'                 => $traitAlias['name'],
                    'alias'                => $traitAlias['alias']
                ]);
            }
        }

        if (isset($rawData['traitPrecedences'])) {
            foreach ($rawData['traitPrecedences'] as $traitPrecedence) {
                $this->storage->insert(IndexStorageItemEnum::STRUCTURES_TRAITS_PRECEDENCES, [
                    'structure_id'         => $seId,
                    'trait_structure_fqcn' => $this->typeAnalyzer->getNormalizedFqcn($traitPrecedence['trait']),
                    'name'                 => $traitPrecedence['name']
                ]);
            }
        }

        foreach ($rawData['properties'] as $property) {
            $accessModifier = $this->parseAccessModifier($property);

            $this->indexProperty(
                $property,
                $filePath,
                $fileId,
                $seId,
                $accessModifierMap[$accessModifier],
                $fileTypeResolver
            );
        }

        foreach ($rawData['methods'] as $method) {
            $accessModifier = $this->parseAccessModifier($method);

            $this->indexFunction(
                $method,
                $fileId,
                $seId,
                $accessModifierMap[$accessModifier],
                false,
                $fileTypeResolver
            );
        }

        foreach ($rawData['constants'] as $constant) {
            $accessModifier = $this->parseAccessModifier($constant);

            $this->indexConstant(
                $constant,
                $filePath,
                $fileId,
                $seId,
                $accessModifier ? $accessModifierMap[$accessModifier] : null,
                $fileTypeResolver
            );
        }

        // Index magic properties.
        $magicProperties = array_merge(
            $documentation['properties'],
            $documentation['propertiesReadOnly'],
            $documentation['propertiesWriteOnly']
        );

        foreach ($magicProperties as $propertyName => $propertyData) {
            // Use the same line as the class definition, it matters for e.g. type resolution.
            $propertyData['name'] = mb_substr($propertyName, 1);
            $propertyData['startLine'] = $propertyData['endLine'] = $rawData['startLine'];

            $this->indexMagicProperty(
                $propertyData,
                $fileId,
                $seId,
                $accessModifierMap['public'],
                $fileTypeResolver
            );
        }

        // Index magic methods.
        foreach ($documentation['methods'] as $methodName => $methodData) {
            // Use the same line as the class definition, it matters for e.g. type resolution.
            $methodData['name'] = $methodName;
            $methodData['startLine'] = $methodData['endLine'] = $rawData['startLine'];

            $this->indexMagicMethod(
                $methodData,
                $fileId,
                $seId,
                $accessModifierMap['public'],
                true,
                $fileTypeResolver
            );
        }

        return $seId;
    }

    /**
     * @param string           $typeSpecification
     * @param int              $line
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return array[]
     */
    protected function getTypeDataForTypeSpecification(
        string $typeSpecification,
        int $line,
        FileTypeResolver $fileTypeResolver
    ): array {
        $typeList = $this->typeAnalyzer->getTypesForTypeSpecification($typeSpecification);

        return $this->getTypeDataForTypeList($typeList, $line, $fileTypeResolver);
    }

    /**
     * @param string[]         $typeList
     * @param int              $line
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return array[]
     */
    protected function getTypeDataForTypeList(array $typeList, int $line, FileTypeResolver $fileTypeResolver): array
    {
        $types = [];

        foreach ($typeList as $type) {
            $types[] = [
                'type' => $type,
                'fqcn' => $fileTypeResolver->resolve($type, $line)
            ];
        }

        return $types;
    }

    /**
     * Indexes the specified constant.
     *
     * @param array            $rawData
     * @param string           $filePath
     * @param int              $fileId
     * @param int|null         $seId
     * @param int|null         $amId
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return void
     */
    protected function indexConstant(
        array $rawData,
        string $filePath,
        int $fileId,
        ?int $seId,
        ?int $amId,
        FileTypeResolver $fileTypeResolver
    ): void {
        $documentation = $this->docblockParser->parse($rawData['docComment'], [
            DocblockParser::VAR_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION
        ], $rawData['name']);

        $varDocumentation = isset($documentation['var']['$' . $rawData['name']]) ?
            $documentation['var']['$' . $rawData['name']] :
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
                $rawData['startLine'],
                $fileTypeResolver
            );
        } elseif ($rawData['defaultValueNode']) {
            $typeList = $this->nodeTypeDeducer->deduce(
                $rawData['defaultValueNode'],
                $filePath,
                $rawData['defaultValue'],
                0
            );

            $types = $this->getTypeDataForTypeList($typeList, $rawData['startLine'], $fileTypeResolver);
        }

        $constantId = $this->storage->insert(IndexStorageItemEnum::CONSTANTS, [
            'name'                  => $rawData['name'],
            'fqcn'                  => isset($rawData['fqcn']) ? $rawData['fqcn'] : null,
            'file_id'               => $fileId,
            'start_line'            => $rawData['startLine'],
            'end_line'              => $rawData['endLine'],
            'default_value'         => $rawData['defaultValue'],
            'is_builtin'            => 0,
            'is_deprecated'         => $documentation['deprecated'] ? 1 : 0,
            'has_docblock'          => empty($rawData['docComment']) ? 0 : 1,
            'short_description'     => $shortDescription,
            'long_description'      => $documentation['descriptions']['long'],
            'type_description'      => $varDocumentation ? $varDocumentation['description'] : null,
            'types_serialized'      => serialize($types),
            'structure_id'          => $seId,
            'access_modifier_id'    => $amId
        ]);
    }

    /**
     * Indexes the specified property.
     *
     * @param array            $rawData
     * @param string           $filePath
     * @param int              $fileId
     * @param int              $seId
     * @param int              $amId
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return void
     */
    protected function indexProperty(
        array $rawData,
        string $filePath,
        int $fileId,
        int $seId,
        int $amId,
        FileTypeResolver $fileTypeResolver
    ): void {
        $documentation = $this->docblockParser->parse($rawData['docComment'], [
            DocblockParser::VAR_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION
        ], $rawData['name']);

        $varDocumentation = isset($documentation['var']['$' . $rawData['name']]) ?
            $documentation['var']['$' . $rawData['name']] :
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
                $rawData['startLine'],
                $fileTypeResolver
            );
        } elseif ($rawData['defaultValueNode']) {
            $typeList = $this->nodeTypeDeducer->deduce(
                $rawData['defaultValueNode'],
                $filePath,
                $rawData['defaultValue'],
                0
            );

            $types = $this->getTypeDataForTypeList($typeList, $rawData['startLine'], $fileTypeResolver);
        }

        $propertyId = $this->storage->insert(IndexStorageItemEnum::PROPERTIES, [
            'name'                  => $rawData['name'],
            'file_id'               => $fileId,
            'start_line'            => $rawData['startLine'],
            'end_line'              => $rawData['endLine'],
            'default_value'         => $rawData['defaultValue'],
            'is_deprecated'         => $documentation['deprecated'] ? 1 : 0,
            'is_magic'              => 0,
            'is_static'             => $rawData['isStatic'] ? 1 : 0,
            'has_docblock'          => empty($rawData['docComment']) ? 0 : 1,
            'short_description'     => $shortDescription,
            'long_description'      => $documentation['descriptions']['long'],
            'type_description'      => $varDocumentation ? $varDocumentation['description'] : null,
            'structure_id'          => $seId,
            'access_modifier_id'    => $amId,
            'types_serialized'      => serialize($types)
        ]);
    }

    /**
     * @param array            $rawData
     * @param int              $fileId
     * @param int              $seId
     * @param int              $amId
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return void
     */
    protected function indexMagicProperty(
        array $rawData,
        int $fileId,
        int $seId,
        int $amId,
        FileTypeResolver $fileTypeResolver
    ): void {
        $types = [];

        if ($rawData['type']) {
            $types = $this->getTypeDataForTypeSpecification(
                $rawData['type'],
                $rawData['startLine'],
                $fileTypeResolver
            );
        }

        $propertyId = $this->storage->insert(IndexStorageItemEnum::PROPERTIES, [
            'name'                  => $rawData['name'],
            'file_id'               => $fileId,
            'start_line'            => $rawData['startLine'],
            'end_line'              => $rawData['endLine'],
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
     * Indexes the specified function.
     *
     * @param array            $rawData
     * @param int              $fileId
     * @param int|null         $seId
     * @param int|null         $amId
     * @param bool             $isMagic
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return void
     */
    protected function indexFunction(
        array $rawData,
        int $fileId,
        ?int $seId,
        ?int $amId,
        bool $isMagic,
        FileTypeResolver $fileTypeResolver
    ): void {
        $documentation = $this->docblockParser->parse($rawData['docComment'], [
            DocblockParser::THROWS,
            DocblockParser::PARAM_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION,
            DocblockParser::RETURN_VALUE
        ], $rawData['name']);

        $returnTypes = [];

        if ($documentation && $documentation['return']['type']) {
            $returnTypes = $this->getTypeDataForTypeSpecification(
                $documentation['return']['type'],
                $rawData['startLine'],
                $fileTypeResolver
            );
        } elseif (isset($rawData['returnType'])) {
            $returnTypes = [
                [
                    'type' => $rawData['returnType'],
                    'fqcn' => isset($rawData['fullReturnType']) ? $rawData['fullReturnType'] : $rawData['returnType']
                ]
            ];

            if (isset($rawData['isReturnTypeNullable']) && $rawData['isReturnTypeNullable']) {
                $returnTypes[] = ['type' => 'null', 'fqcn' => 'null'];
            }
        }

        $throws = [];

        foreach ($documentation['throws'] as $type => $description) {
            $typeData = $this->getTypeDataForTypeSpecification($type, $rawData['startLine'], $fileTypeResolver);
            $typeData = array_shift($typeData);

            $throwsData = [
                'type'        => $typeData['type'],
                'full_type'   => $typeData['fqcn'],
                'description' => $description ?: null
            ];

            $throws[] = $throwsData;
        }

        $parameters = [];

        foreach ($rawData['parameters'] as $parameter) {
            $parameterKey = '$' . $parameter['name'];
            $parameterDoc = isset($documentation['params'][$parameterKey]) ?
                $documentation['params'][$parameterKey] : null;

            $types = [];

            if ($parameterDoc) {
                $types = $this->getTypeDataForTypeSpecification(
                    $parameterDoc['type'],
                    $rawData['startLine'],
                    $fileTypeResolver
                );
            } elseif (isset($parameter['type'])) {
                $parameterType = $parameter['type'];
                $parameterFullType = isset($parameter['fullType']) ? $parameter['fullType'] : $parameterType;

                if ($parameter['isVariadic']) {
                    $parameterType .= '[]';
                    $parameterFullType .= '[]';
                }

                $types = [
                    [
                        'type' => $parameterType,
                        'fqcn' => $parameterFullType
                    ]
                ];

                if ($parameter['isNullable']) {
                    $types[] = [
                        'type' => 'null',
                        'fqcn' => 'null'
                    ];
                }
            }

            $parameters[] = [
                'name'             => $parameter['name'],
                'type_hint'        => $parameter['type'],
                'types_serialized' => serialize($types),
                'description'      => $parameterDoc ? $parameterDoc['description'] : null,
                'default_value'    => $parameter['defaultValue'],
                'is_nullable'      => $parameter['isNullable'] ? 1 : 0,
                'is_reference'     => $parameter['isReference'] ? 1 : 0,
                'is_optional'      => $parameter['isOptional'] ? 1 : 0,
                'is_variadic'      => $parameter['isVariadic'] ? 1 : 0
            ];
        }

        $functionId = $this->storage->insert(IndexStorageItemEnum::FUNCTIONS, [
            'name'                    => $rawData['name'],
            'fqcn'                    => isset($rawData['fqcn']) ? $rawData['fqcn'] : null,
            'file_id'                 => $fileId,
            'start_line'              => $rawData['startLine'],
            'end_line'                => $rawData['endLine'],
            'is_builtin'              => 0,
            'is_abstract'             => (isset($rawData['isAbstract']) && $rawData['isAbstract']) ? 1 : 0,
            'is_final'                => (isset($rawData['isFinal']) && $rawData['isFinal']) ? 1 : 0,
            'is_deprecated'           => $documentation['deprecated'] ? 1 : 0,
            'short_description'       => $documentation['descriptions']['short'],
            'long_description'        => $documentation['descriptions']['long'],
            'return_description'      => $documentation['return']['description'],
            'return_type_hint'        => $rawData['returnType'],
            'structure_id'            => $seId,
            'access_modifier_id'      => $amId,
            'is_magic'                => $isMagic ? 1 : 0,
            'is_static'               => isset($rawData['isStatic']) ? ($rawData['isStatic'] ? 1 : 0) : 0,
            'has_docblock'            => empty($rawData['docComment']) ? 0 : 1,
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
     * @param array            $rawData
     * @param int              $fileId
     * @param int|null         $seId
     * @param int|null         $amId
     * @param bool             $isMagic
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return void
     */
    protected function indexMagicMethod(
        array $rawData,
        int $fileId,
        ?int $seId,
        ?int $amId,
        bool $isMagic,
        FileTypeResolver $fileTypeResolver
    ): void {
        $returnTypes = [];

        if ($rawData['type']) {
            $returnTypes = $this->getTypeDataForTypeSpecification(
                $rawData['type'],
                $rawData['startLine'],
                $fileTypeResolver
            );
        }

        $parameters = [];

        foreach ($rawData['requiredParameters'] as $parameterName => $parameter) {
            $types = [];

            if ($parameter['type']) {
                $types = $this->getTypeDataForTypeSpecification(
                    $parameter['type'],
                    $rawData['startLine'],
                    $fileTypeResolver
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
                    $rawData['startLine'],
                    $fileTypeResolver
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
            'start_line'              => $rawData['startLine'],
            'end_line'                => $rawData['endLine'],
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
     * @param array $rawData
     * @param bool  $returnNull
     *
     * @throws UnexpectedValueException
     *
     * @return string|null
     */
    protected function parseAccessModifier(array $rawData, bool $returnNull = false): ?string
    {
        if (isset($rawData['isPublic']) && $rawData['isPublic']) {
            return 'public';
        } elseif (isset($rawData['isProtected']) && $rawData['isProtected']) {
            return 'protected';
        } elseif (isset($rawData['isPrivate']) && $rawData['isPrivate']) {
            return 'private';
        } elseif ($returnNull) {
            return null;
        }

        throw new UnexpectedValueException('Unknown access modifier returned!');
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
