<?php

namespace PhpIntegrator\Indexing\Visiting;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\TypeNormalizerInterface;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolver;
use PhpIntegrator\Analysis\Typing\Resolving\TypeResolverInterface;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\IndexStorageItemEnum;

use PhpIntegrator\Parsing\DocblockParser;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that traverses a set of nodes, indexing global functions in the process.
 */
final class GlobalFunctionIndexingVisitor extends NodeVisitorAbstract
{
    /**
     * @var FileTypeResolverFactoryInterface
     */
    private $fileTypeResolverFactory;

    /**
     * @var TypeNormalizerInterface
     */
    private $typeNormalizer;

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
     * @var TypeResolverInterface
     */
    private $typeResolver;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

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
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param TypeNormalizerInterface          $typeNormalizer
     * @param StorageInterface                 $storage
     * @param DocblockParser                   $docblockParser
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param TypeResolverInterface            $typeResolver
     * @param NodeTypeDeducerInterface         $nodeTypeDeducer
     * @param int                              $fileId
     * @param string                           $code
     * @param string                           $filePath
     */
    public function __construct(
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        TypeNormalizerInterface $typeNormalizer,
        StorageInterface $storage,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        TypeResolverInterface $typeResolver,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        int $fileId,
        string $code,
        string $filePath
    ) {
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->typeNormalizer = $typeNormalizer;
        $this->storage = $storage;
        $this->docblockParser = $docblockParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->typeResolver = $typeResolver;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->fileId = $fileId;
        $this->code = $code;
        $this->filePath = $filePath;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Function_) {
            $this->parseFunctionNode($node);
        }
    }

    /**
     * @param Node\Stmt\Function_ $node
     *
     * @return void
     */
    protected function parseFunctionNode(Node\Stmt\Function_ $node): void
    {
        $fqcn = $this->typeNormalizer->getNormalizedFqcn(
            isset($node->namespacedName) ? $node->namespacedName->toString() : $node->name
        );

        $fileTypeResolver = $this->fileTypeResolverFactory->create($this->filePath);

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
                $node->getLine(),
                $fileTypeResolver
            );
        } elseif ($resolvedType) {
            $returnTypes = [
                [
                    'type' => $localType,
                    'fqcn' => $resolvedType ? $resolvedType : $localType
                ]
            ];

            if ($node->getReturnType() instanceof Node\NullableType) {
                $returnTypes[] = ['type' => 'null', 'fqcn' => 'null'];
            }
        }

        $throws = [];

        foreach ($documentation['throws'] as $type => $description) {
            $typeData = $this->getTypeDataForTypeSpecification($type, $node->getLine(), $fileTypeResolver);
            $typeData = array_shift($typeData);

            $throwsData = [
                'type'        => $typeData['type'],
                'full_type'   => $typeData['fqcn'],
                'description' => $description ?: null
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
                    $node->getLine(),
                    $fileTypeResolver
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

        $functionId = $this->storage->insert(IndexStorageItemEnum::FUNCTIONS, [
            'name'                    => $node->name,
            'fqcn'                    => $fqcn,
            'file_id'                 => $this->fileId,
            'start_line'              => $node->getLine(),
            'end_line'                => $node->getAttribute('endLine'),
            'is_builtin'              => 0,
            'is_abstract'             => 0,
            'is_final'                => 0,
            'is_deprecated'           => $documentation['deprecated'] ? 1 : 0,
            'short_description'       => $documentation['descriptions']['short'],
            'long_description'        => $documentation['descriptions']['long'],
            'return_description'      => $documentation['return']['description'],
            'return_type_hint'        => $localType,
            'structure_id'            => null,
            'access_modifier_id'      => null,
            'is_magic'                => 0,
            'is_static'               => 0,
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
}
