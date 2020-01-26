<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Sockets\JsonRpcQueueItem;

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
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $this->exit();

        // This is a notification that doesn't expect a response.
        $deferred = new Deferred();
        $deferred->resolve(null);

        return $deferred->promise();
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
