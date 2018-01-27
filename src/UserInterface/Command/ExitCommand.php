<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Indexing\ManagerRegistry;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;

/**
 * Command that requests the server to shutdown completely and exit.
 */
final class ExitCommand extends AbstractCommand
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->exit($queueItem->getJsonRpcResponseSender())
        );
    }

    /**
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     */
    public function exit(JsonRpcResponseSenderInterface $jsonRpcResponseSender): void
    {
        $this->managerRegistry->ensureConnectionClosed();

        exit(0);
    }
}
