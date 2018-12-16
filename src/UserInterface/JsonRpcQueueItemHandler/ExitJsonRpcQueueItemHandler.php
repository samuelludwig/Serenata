<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * JsonRpcQueueItemHandlerthat handles the "exit" request.
 */
final class ExitJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var ActiveWorkspaceManager
     */
    private $activeWorkspaceManager;

    /**
     * @param ActiveWorkspaceManager $activeWorkspaceManager
     */
    public function __construct(ActiveWorkspaceManager $activeWorkspaceManager)
    {
        $this->activeWorkspaceManager = $activeWorkspaceManager;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $this->exit();

        return null;
    }

    /**
     * @return void
     */
    public function exit(): void
    {
        // Assume that an active workspace means that shutdown hasn't been invoked yet, in which case we need to send
        // an error code.
        exit($this->activeWorkspaceManager->getActiveWorkspace() !== null ? 1 : 0);
    }
}
