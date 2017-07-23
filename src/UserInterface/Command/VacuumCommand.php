<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Indexing\ProjectIndexer;

use PhpIntegrator\Sockets\JsonRpcRequest;

/**
 * Command that vacuums a project.
 *
 * Vacuuming includes cleaning up the index, i.e. removing files that no longer exist.
 */
class VacuumCommand extends AbstractCommand
{
    /**
     * @var ProjectIndexer
     */
    private $projectIndexer;

    /**
     * @param ProjectIndexer $projectIndexer
     */
    public function __construct(ProjectIndexer $projectIndexer)
    {
        $this->projectIndexer = $projectIndexer;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcRequest $request)
    {
        return $this->vacuum();;
    }

    /**
     * @return bool
     */
    public function vacuum(): bool
    {
        $this->projectIndexer->pruneRemovedFiles();

        return true;
    }
}
