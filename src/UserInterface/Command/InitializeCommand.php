<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Indexing\IndexDatabase;
use PhpIntegrator\Indexing\BuiltinIndexer;
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
     * @var BuiltinIndexer
     */
    protected $builtinIndexer;

    /**
     * @var ProjectIndexer
     */
    protected $projectIndexer;

    /**
     * @param IndexDatabase  $indexDatabase
     * @param BuiltinIndexer $builtinIndexer
     * @param ProjectIndexer $projectIndexer
     */
    public function __construct(
        IndexDatabase $indexDatabase,
        BuiltinIndexer $builtinIndexer,
        ProjectIndexer $projectIndexer
    ) {
        $this->indexDatabase = $indexDatabase;
        $this->builtinIndexer = $builtinIndexer;
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
     * @param bool $includeBuiltinItems
     *
     * @return bool
     */
    public function initialize($includeBuiltinItems = true)
    {
        $this->ensureIndexDatabaseDoesNotExist();

        $this->indexDatabase->initialize();

        if ($includeBuiltinItems) {
            $this->builtinIndexer->index();
        }

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
