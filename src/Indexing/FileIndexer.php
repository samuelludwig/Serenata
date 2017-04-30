<?php

namespace PhpIntegrator\Indexing;

use DateTime;
use Exception;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use PhpIntegrator\Parsing\DocblockParser;

use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

/**
 * Handles indexation of PHP code in a single file.
 *
 * The index only contains "direct" data, meaning that it only contains data that is directly attached to an element.
 * For example, classes will only have their direct members attached in the index. The index will also keep track of
 * links between structural elements and parents, implemented interfaces, and more, but it will not duplicate data,
 * meaning parent methods will not be copied and attached to child classes.
 *
 * The index keeps track of 'outlines' that are confined to a single file. It in itself does not do anything
 * "intelligent" such as automatically inheriting docblocks from overridden methods.
 */
class FileIndexer implements FileIndexerInterface
{
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
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @param StorageInterface                           $storage
     * @param TypeAnalyzer                               $typeAnalyzer
     * @param DocblockParser                             $docblockParser
     * @param NodeTypeDeducerInterface                   $nodeTypeDeducer
     * @param Parser                                     $parser
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     */
    public function __construct(
        StorageInterface $storage,
        TypeAnalyzer $typeAnalyzer,
        DocblockParser $docblockParser,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        Parser $parser,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
    ) {
        $this->storage = $storage;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockParser = $docblockParser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->parser = $parser;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
    }

    /**
     * @inheritDoc
     */
    public function index(string $filePath, string $code): void
    {
        $handler = new ErrorHandler\Collecting();

        try {
            $nodes = $this->parser->parse($code, $handler);

            if ($nodes === null) {
                throw new Error('Unknown syntax error encountered');
            }
        } catch (Error $e) {
            throw new IndexingFailedException($e->getMessage(), 0, $e);
        }

        $this->storage->beginTransaction();

        $this->storage->deleteFile($filePath);

        $fileId = $this->storage->insert(IndexStorageItemEnum::FILES, [
            'path'         => $filePath,
            'indexed_time' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        try {
            $traverser = $this->createTraverser($nodes, $filePath, $code, $fileId);
            $traverser->traverse($nodes);

            $this->storage->commitTransaction();
        } catch (Error $e) {
            $this->storage->rollbackTransaction();

            throw new IndexingFailedException($e->getMessage(), 0, $e);
        } catch (Exception $e) {
            $this->storage->rollbackTransaction();

            throw $e;
        }
    }

    /**
     * @param array  $nodes
     * @param string $filePath
     * @param string $code
     * @param int    $fileId
     *
     * @return NodeTraverser
     */
    protected function createTraverser(array $nodes, string $filePath, string $code, int $fileId): NodeTraverser
    {
        $globalConstantIndexingVisitor = new Visiting\GlobalConstantIndexingVisitor(
            $this->storage,
            $this->docblockParser,
            $this->structureAwareNameResolverFactory,
            $this->typeAnalyzer,
            $this->nodeTypeDeducer,
            $fileId,
            $code,
            $filePath
        );

        $globalDefineIndexingVisitor = new Visiting\GlobalDefineIndexingVisitor(
            $this->storage,
            $this->nodeTypeDeducer,
            $fileId,
            $code,
            $filePath
        );

        $globalFunctionIndexingVisitor = new Visiting\GlobalFunctionIndexingVisitor(
            $this->structureAwareNameResolverFactory,
            $this->storage,
            $this->docblockParser,
            $this->typeAnalyzer,
            $fileId,
            $code,
            $filePath
        );

        $classlikeIndexingVisitor = new Visiting\ClasslikeIndexingVisitor(
            $this->storage,
            $this->typeAnalyzer,
            $this->docblockParser,
            $this->nodeTypeDeducer,
            $this->structureAwareNameResolverFactory,
            $fileId,
            $code,
            $filePath
        );

        // TODO: Refactor to traverse once.
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new Visiting\UseStatementIndexingVisitor($this->storage, $fileId, $code));
        $traverser->traverse($nodes);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($globalDefineIndexingVisitor);
        $traverser->addVisitor($globalConstantIndexingVisitor);
        $traverser->addVisitor($globalFunctionIndexingVisitor);
        $traverser->addVisitor($classlikeIndexingVisitor);

        return $traverser;
    }
}
