<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Indexing\IndexFilePruner;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

/**
 * Command that vacuums a project.
 *
 * Vacuuming includes cleaning up the index, i.e. removing files that no longer exist.
 */
class VacuumCommand extends AbstractCommand
{
    /**
     * @var IndexFilePruner
     */
    private $indexFilePruner;

    /**
     * @param IndexFilePruner $indexFilePruner
     */
    public function __construct(IndexFilePruner $indexFilePruner)
    {
        $this->indexFilePruner = $indexFilePruner;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        return new JsonRpcResponse($queueItem->getRequest()->getId(), $this->vacuum());
    }

    /**
     * @return bool
     */
    public function vacuum(): bool
    {
        $this->indexFilePruner->prune();

        return true;
    }
}
