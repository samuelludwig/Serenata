<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;

use PhpIntegrator\Indexing\IndexDatabase;
use PhpIntegrator\Indexing\ProjectIndexer;
use PhpIntegrator\Indexing\BuiltinIndexer;

/**
 * Command that initializes a project.
 */
class InitializeCommand extends AbstractCommand
{
    /**
     * @var IndexDatabase
     */
    private $indexDatabase;

    /**
     * @var BuiltinIndexer
     */
    private $builtinIndexer;

    /**
     * @var ProjectIndexer
     */
    private $projectIndexer;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param IndexDatabase  $indexDatabase
     * @param BuiltinIndexer $builtinIndexer
     * @param ProjectIndexer $projectIndexer
     * @param Cache          $cache
     */
    public function __construct(
        IndexDatabase $indexDatabase,
        BuiltinIndexer $builtinIndexer,
        ProjectIndexer $projectIndexer,
        Cache $cache
    ) {
        $this->indexDatabase = $indexDatabase;
        $this->builtinIndexer = $builtinIndexer;
        $this->projectIndexer = $projectIndexer;
        $this->cache = $cache;
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
    public function initialize(bool $includeBuiltinItems = true): bool
    {
        $this->ensureIndexDatabaseDoesNotExist();

        $this->indexDatabase->initialize();

        if ($includeBuiltinItems) {
            $this->builtinIndexer->index();
        }

        $this->clearCache();

        return true;
    }

    /**
     * @return void
     */
    protected function ensureIndexDatabaseDoesNotExist(): void
    {
        if (file_exists($this->indexDatabase->getDatabasePath())) {
            $this->indexDatabase->ensureConnectionClosed();

            unlink($this->indexDatabase->getDatabasePath());
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
