<?php

namespace PhpIntegrator\Tests\Unit\Indexing;

use DateTime;

use PhpIntegrator\Indexing\Structures;
use PhpIntegrator\Indexing\IndexFilePruner;
use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\FileExistenceCheckerInterface;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class FilePruningTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testDoesNothingWhenThereIsNothingToPrune(): void
    {
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $fileExistenceChecker = $this->getMockBuilder(FileExistenceCheckerInterface::class)->getMock();

        $storage->expects($this->once())->method('getFiles')->willReturn([
            new Structures\File('testPath.php', new DateTime(), [])
        ]);

        $pruner = new IndexFilePruner($storage, $fileExistenceChecker);

        $fileExistenceChecker->expects($this->once())->method('exists')->with('testPath.php')->willReturn(true);
        $storage->expects($this->never())->method('delete');

        $pruner->prune();
    }

    /**
     * @return void
     */
    public function testPrunesFileThatNoLongerExists(): void
    {
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $fileExistenceChecker = $this->getMockBuilder(FileExistenceCheckerInterface::class)->getMock();

        $storage->expects($this->once())->method('getFiles')->willReturn([
            new Structures\File('testPath.php', new DateTime(), [])
        ]);

        $pruner = new IndexFilePruner($storage, $fileExistenceChecker);

        $fileExistenceChecker->expects($this->once())->method('exists')->with('testPath.php')->willReturn(false);
        $storage->expects($this->once())->method('delete');

        $pruner->prune();
    }
}
