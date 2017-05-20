<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Indexing\StorageVersionChecker;
use PhpIntegrator\Indexing\IncorrectDatabaseVersionException;

/**
 * Command that tests a project to see if it is in a properly usable state.
 */
class TestCommand extends AbstractCommand
{
    /**
     * @var StorageVersionChecker
     */
    private $storageVersionChecker;

    /**
     * @param StorageVersionChecker  $storageVersionChecker
     */
    public function __construct(StorageVersionChecker $storageVersionChecker)
    {
        $this->storageVersionChecker = $storageVersionChecker;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        $success = $this->test();

        return $success;
    }

    /**
     * @return bool
     */
    public function test(): bool
    {
        return $this->storageVersionChecker->isUpToDate();
    }
}
