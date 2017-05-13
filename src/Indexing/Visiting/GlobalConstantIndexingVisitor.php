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
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

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
     * @param StorageInterface                           $storage
     * @param DocblockParser                             $docblockParser
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param TypeAnalyzer                               $typeAnalyzer
     * @param NodeTypeDeducerInterface                   $nodeTypeDeducer
     * @param Structures\File                            $file
     * @param string                                     $code
     * @param string                                     $filePath
     */
    public function __construct(
        StorageInterface $storage,
        DocblockParser $docblockParser,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        TypeAnalyzer $typeAnalyzer,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        Structures\File $file,
        string $code,
        string $filePath
    ) {
        $this->storage = $storage;
        $this->docblockParser = $docblockParser;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->file = $file;
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
        $filePosition = new FilePosition($this->filePath, new Position($node->getLine(), 0));

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

            $types = $this->getTypeDataForTypeSpecification($varDocumentation['type'], $filePosition);
        } elseif ($node->value) {
            $typeList = $this->nodeTypeDeducer->deduce($node->value, $this->filePath, $defaultValue, 0);

            $types = array_map(function (string $type) {
                return [
                    'type' => $type,
                    'fqcn' => $type
                ];
            }, $typeList);
        }

        $constant = new Structures\Constant(
            $node->name,
            '\\' . $node->namespacedName->toString(),
            $this->file,
            $node->getLine(),
            $node->getAttribute('endLine'),
            $defaultValue,
            false,
            $documentation['deprecated'],
            !empty($docComment),
            $shortDescription,
            $documentation['descriptions']['long'],
            $varDocumentation ? $varDocumentation['description'] : null,
            $types,
            null,
            null
        );

        $this->storage->persist($constant);
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
     * @return array[]
     */
    protected function getTypeDataForTypeList(array $typeList, FilePosition $filePosition): array
    {
        $types = [];

        $positionalNameResolver = $this->structureAwareNameResolverFactory->create($filePosition);

        foreach ($typeList as $type) {
            $types[] = [
                'type' => $type,
                'fqcn' => $positionalNameResolver->resolve($type, $filePosition)
            ];
        }

        return $types;
    }
}
