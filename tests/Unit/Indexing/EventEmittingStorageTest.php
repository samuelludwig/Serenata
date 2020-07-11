<?php

namespace Serenata\Tests\Unit\Indexing;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\IndexingEventName;
use Serenata\Indexing\EventEmittingStorage;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class EventEmittingStorageTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSendsEventOnceOnCommitAndNotMultipleTimes(): void
    {
        $delegate = $this->getMockBuilder(StorageInterface::class)->getMock();
        $classlike  = $this->getMockBuilder(Structures\Classlike::class)->getMock();

        $storage = new EventEmittingStorage($delegate);

        $timesHit = 0;

        $storage->on(IndexingEventName::CLASSLIKE_UPDATED, function () use (&$timesHit) {
            ++$timesHit;
        });

        $storage->persist($classlike);
        $storage->persist($classlike);

        $storage->commitTransaction();

        self::assertEquals(1, $timesHit);
    }

    /**
     * @return void
     */
    public function testDoesNotSendEventOnRollback(): void
    {
        $delegate = $this->getMockBuilder(StorageInterface::class)->getMock();
        $classlike  = $this->getMockBuilder(Structures\Classlike::class)->getMock();

        $storage = new EventEmittingStorage($delegate);

        $timesHit = 0;

        $storage->on(IndexingEventName::CLASSLIKE_UPDATED, function () use (&$timesHit) {
            ++$timesHit;
        });

        $storage->persist($classlike);

        $storage->rollbackTransaction();

        self::assertEquals(0, $timesHit);
    }
}
