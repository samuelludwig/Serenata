<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\ClearableCacheInterface;

use Serenata\Indexing\ManagerRegistry;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * Handles the shutdown request.
 */
final class ShutdownCommand extends AbstractCommand
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var ActiveWorkspaceManager
     */
    private $activeWorkspaceManager;

    /**
     * @var ClearableCacheInterface
     */
    private $cache;

    /**
     * @param ManagerRegistry         $managerRegistry
     * @param ActiveWorkspaceManager  $activeWorkspaceManager
     * @param ClearableCacheInterface $cache
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ActiveWorkspaceManager $activeWorkspaceManager,
        ClearableCacheInterface $cache
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->activeWorkspaceManager = $activeWorkspaceManager;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $this->shutdown();

        return new JsonRpcResponse($queueItem->getRequest()->getId(), null);
    }

    /**
     * @return void
     */
    public function shutdown(): void
    {
        $this->managerRegistry->ensureConnectionClosed();

        $this->activeWorkspaceManager->setActiveWorkspace(null);

        $this->cache->clearCache();
    }
}
