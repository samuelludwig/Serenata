<?php

namespace PhpIntegrator\Indexing;

use DateTime;
use Exception;

use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

/**
 * Handles indexation of a PHP meta file.
 */
class MetaFileIndexer implements FileIndexerInterface
{
    /**
     * The storage to use for index data.
     *
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var int
     */
    private $fileId;

    /**
     * @param StorageInterface $storage
     * @param Parser           $parser
     */
    public function __construct(StorageInterface $storage, Parser $parser)
    {
        $this->storage = $storage;
        $this->parser = $parser;
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
            throw new IndexingFailedException('The code could not be parsed', 0, $e);
        }

        $this->storage->beginTransaction();

        $this->storage->deleteFile($filePath);

        $fileId = $this->storage->insert(IndexStorageItemEnum::FILES, [
            'path'         => $filePath,
            'indexed_time' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        try {
            $metaFileIndexingVisitor = new Visiting\MetaFileIndexingVisitor(
                $this->storage,
                $fileId
            );

            $traverser = new NodeTraverser(false);
            $traverser->addVisitor($metaFileIndexingVisitor);
            $traverser->traverse($nodes);

            $this->storage->commitTransaction();
        } catch (Exception $e) {
            $this->storage->rollbackTransaction();

            throw new IndexingFailedException($e->getMessage(), 0, $e);
        }
    }
}
