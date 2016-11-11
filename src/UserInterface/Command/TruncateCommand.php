<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;

use GetOptionKit\OptionCollection;

use PhpIntegrator\Indexing\IndexDatabase;

/**
 * Command that truncates the database.
 */
class TruncateCommand extends AbstractCommand
{
    /**
     * @var IndexDatabase
     */
    protected $indexDatabase;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param IndexDatabase $database
     * @param Cache         $cache
     */
    public function __construct(IndexDatabase $database, Cache $cache)
    {
        $this->indexDatabase = $indexDatabase;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function attachOptions(OptionCollection $optionCollection)
    {

    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        $success = $this->truncate();

        return null;
    }

    /**
     * @return bool
     */
    public function truncate()
    {
        @unlink($this->indexDatabase->getDatabasePath());``

        if ($this->cache instanceof ClearableCache) {
            $this->cache->deleteAll();
        }

        return true;
    }
}
