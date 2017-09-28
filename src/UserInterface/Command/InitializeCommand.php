<?php

namespace PhpIntegrator\UserInterface\Command;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;

use PhpIntegrator\Indexing\Indexer;
use PhpIntegrator\Indexing\ManagerRegistry;
use PhpIntegrator\Indexing\SchemaInitializer;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;

/**
 * Command that initializes a project.
 */
final class InitializeCommand extends AbstractCommand
{
    /**
     * @var SchemaInitializer
     */
    private $schemaInitializer;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param SchemaInitializer $schemaInitializer
     * @param ManagerRegistry   $managerRegistry
     * @param Indexer           $indexer
     * @param Cache             $cache
     */
    public function __construct(
        SchemaInitializer $schemaInitializer,
        ManagerRegistry $managerRegistry,
        Indexer $indexer,
        Cache $cache
    ) {
        $this->schemaInitializer = $schemaInitializer;
        $this->managerRegistry = $managerRegistry;
        $this->indexer = $indexer;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->initialize($queueItem->getJsonRpcResponseSender())
        );
    }

    /**
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param bool                           $includeBuiltinItems
     */
    public function initialize(
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        bool $includeBuiltinItems = true
    ): bool {
        $this->ensureIndexDatabaseDoesNotExist();

        $this->schemaInitializer->initialize();

        if ($includeBuiltinItems) {
            $this->indexer->index(
                [__DIR__ . '/../../../vendor/jetbrains/phpstorm-stubs/'],
                ['php'],
                [],
                false,
                $jsonRpcResponseSender,
                null
            );
        }

        $this->clearCache();

        return true;
    }

    /**
     * @return void
     */
    protected function ensureIndexDatabaseDoesNotExist(): void
    {
        if (file_exists($this->managerRegistry->getDatabasePath())) {
            $this->managerRegistry->ensureConnectionClosed();

            unlink($this->managerRegistry->getDatabasePath());
        }
    }

    /**
     * @return void
     */
    protected function clearCache(): void
    {
        if ($this->cache instanceof ClearableCache) {
            $this->cache->deleteAll();
        }
    }
}
