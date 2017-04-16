<?php

namespace PhpIntegrator\Indexing\Visiting;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolver;
use PhpIntegrator\Analysis\Typing\Resolving\TypeResolverInterface;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\IndexStorageItemEnum;

use PhpIntegrator\Parsing\DocblockParser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that traverses a set of nodes, indexing global constants in the process.
 */
final class GlobalConstantIndexingVisitor extends NodeVisitorAbstract
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
     * @var FileTypeResolverFactoryInterface
     */
    private $fileTypeResolverFactory;

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
     * @param StorageInterface                 $storage
     * @param DocblockParser                   $docblockParser
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param TypeResolverInterface            $typeResolver
     * @param NodeTypeDeducerInterface         $nodeTypeDeducer
     * @param int                              $fileId
     * @param string                           $code
     * @param string                           $filePath
     */
    public function __construct(
        StorageInterface $storage,
        DocblockParser $docblockParser,
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        TypeAnalyzer $typeAnalyzer,
        TypeResolverInterface $typeResolver,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        int $fileId,
        string $code,
        string $filePath
    ) {
        $this->storage = $storage;
        $this->docblockParser = $docblockParser;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
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
        if ($node instanceof Node\Stmt\Const_) {
            $this->parseConstantStatementNode($node);
        }
    }

    /**
     * @param Node\Stmt\Const_ $node
     *
     * @return void
     */
    protected function parseConstantStatementNode(Node\Stmt\Const_ $node): void
    {
        foreach ($node->consts as $const) {
            $this->parseConstantNode($const, $node);
        }
    }

    /**
     * @param Node\Const_      $node
     * @param Node\Stmt\Const_ $const
     *
     * @return void
     */
    protected function parseConstantNode(Node\Const_ $node, Node\Stmt\Const_ $const): void
    {
        $fileTypeResolver = $this->fileTypeResolverFactory->create($this->filePath);

        $docComment = $const->getDocComment() ? $const->getDocComment()->getText() : null;

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
                $node->getLine(),
                $fileTypeResolver
            );
        } elseif ($node->value) {
            $typeList = $this->nodeTypeDeducer->deduce($node->value, $this->filePath, $defaultValue, 0);

            $types = array_map(function (string $type) {
                return [
                    'type' => $type,
                    'fqcn' => $type
                ];
            }, $typeList);
        }

        $this->storage->insert(IndexStorageItemEnum::CONSTANTS, [
            'name'                  => $node->name,
            'fqcn'                  => '\\' . $node->namespacedName->toString(),
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
            'structure_id'          => null,
            'access_modifier_id'    => null
        ]);
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
