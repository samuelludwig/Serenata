<?php

namespace PhpIntegrator\Tests\Unit\Indexing;

use DateTime;

use PhpIntegrator\Indexing\Structures;
use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\IndexingEventName;
use PhpIntegrator\Indexing\EventEmittingStorage;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class EventEmittingStorageTest extends AbstractIntegrationTest
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

        static::assertEquals(1, $timesHit);
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

        static::assertEquals(0, $timesHit);
    }
}
