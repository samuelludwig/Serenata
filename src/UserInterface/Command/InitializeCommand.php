<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Indexing\IndexDatabase;
use PhpIntegrator\Indexing\ProjectIndexer;

/**
 * Command that initializes a project.
 */
class InitializeCommand extends AbstractCommand
{
    /**
     * @var IndexDatabase
     */
    protected $indexDatabase;

    /**
     * @var ProjectIndexer
     */
    protected $projectIndexer;

    /**
     * @param IndexDatabase  $indexDatabase
     * @param ProjectIndexer $projectIndexer
     */
    public function __construct(IndexDatabase $indexDatabase, ProjectIndexer $projectIndexer)
    {
        $this->indexDatabase = $indexDatabase;
        $this->projectIndexer = $projectIndexer;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        $success = $this->initialize();

        return $success;
    }

    /**
     * @return bool
     */
    public function initialize()
    {
        $this->ensureIndexDatabaseDoesNotExist();

        $this->indexDatabase->initialize();

        $this->projectIndexer->indexBuiltinItemsIfNecessary();

        return true;
    }

    /**
     * @return void
     */
    protected function ensureIndexDatabaseDoesNotExist()
    {
        if (file_exists($this->indexDatabase->getDatabasePath())) {
            $this->indexDatabase->ensureConnectionClosed();

            unlink($this->indexDatabase->getDatabasePath());
        }
    }
}
