<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Indexing\IndexDatabase;

/**
 * Command that shows a list of available namespace.
 */
class NamespaceListCommand extends AbstractCommand
{
    /**
     * @var IndexDatabase
     */
    protected $indexDatabase;

    /**
     * @param IndexDatabase $indexDatabase
     */
    public function __construct(IndexDatabase $indexDatabase)
    {
        $this->indexDatabase = $indexDatabase;
    }

    /**
     * @inheritDoc
     */
    protected function process(ArrayAccess $arguments)
    {
        return $this->outputJson(true, $this->getNamespaceList());
    }

    /**
     * @return array
     */
    public function getNamespaceList()
    {
        return $this->indexDatabase->getNamespaces();
    }
}
