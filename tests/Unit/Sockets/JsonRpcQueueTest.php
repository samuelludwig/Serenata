<?php

namespace Serenata\Tests\Unit\Sockets;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageSenderInterface;
use Serenata\Sockets\JsonRpcRequestPriorityDeterminerInterface;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonRpcQueueTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $requestPriorityDeterminer;

    /// @inherited
    public function setUp()
    {
        $this->requestPriorityDeterminer = $this->getMockBuilder(JsonRpcRequestPriorityDeterminerInterface::class)
            ->setMethods(['determine'])
            ->getMock();
    }

    /**
     * @return void
     */
    public function testPushedItemPopsBackOut(): void
    {
        $queue = new JsonRpcQueue($this->requestPriorityDeterminer);

        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest(null, 'test'),
            $this->getMockBuilder(JsonRpcMessageSenderInterface::class)->getMock()
        );

        $queue->push($queueItem);

        static::assertSame($queueItem, $queue->pop());
    }

    /**
     * @return void
     */
    public function testFirstPushedItemPopsOutFirstIfPriorityIsEqual(): void
    {
        $queue = new JsonRpcQueue($this->requestPriorityDeterminer);

        $queueItem1 = new JsonRpcQueueItem(
            new JsonRpcRequest(null, 'test'),
            $this->getMockBuilder(JsonRpcMessageSenderInterface::class)->getMock()
        );

        $queueItem2 = new JsonRpcQueueItem(
            new JsonRpcRequest(null, 'test'),
            $this->getMockBuilder(JsonRpcMessageSenderInterface::class)->getMock()
        );

        $queue->push($queueItem2, 1);
        $queue->push($queueItem1, 1);

        static::assertSame($queueItem2, $queue->pop());
        static::assertSame($queueItem1, $queue->pop());
    }

    /**
     * @return void
     */
    public function testHigherPriorityItemPopsOutFirst(): void
    {
        $queue = new JsonRpcQueue($this->requestPriorityDeterminer);

        $queueItemHighPriority = new JsonRpcQueueItem(
            new JsonRpcRequest(null, 'test'),
            $this->getMockBuilder(JsonRpcMessageSenderInterface::class)->getMock()
        );

        $queueItemLowPriority = new JsonRpcQueueItem(
            new JsonRpcRequest(null, 'test'),
            $this->getMockBuilder(JsonRpcMessageSenderInterface::class)->getMock()
        );

        $queue->push($queueItemLowPriority, 1);
        $queue->push($queueItemHighPriority, 2);

        static::assertSame($queueItemHighPriority, $queue->pop());
        static::assertSame($queueItemLowPriority, $queue->pop());
    }

    /**
     * @return void
     */
    public function testAutomaticallyDeterminesPriorityIfNotSetExplicitly(): void
    {
        $queue = new JsonRpcQueue($this->requestPriorityDeterminer);

        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest(null, 'test'),
            $this->getMockBuilder(JsonRpcMessageSenderInterface::class)->getMock()
        );

        $this->requestPriorityDeterminer->expects($this->once())->method('determine')->with($queueItem->getRequest());

        $queue->push($queueItem);

        static::assertSame($queueItem, $queue->pop());
    }

    /**
     * @return void
     */
    public function testDoesNotAutomaticallyDeterminePriorityIfSetExplicitly(): void
    {
        $queue = new JsonRpcQueue($this->requestPriorityDeterminer);

        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest(null, 'test'),
            $this->getMockBuilder(JsonRpcMessageSenderInterface::class)->getMock()
        );

        $this->requestPriorityDeterminer->expects($this->never())->method('determine');

        $queue->push($queueItem, 1);

        static::assertSame($queueItem, $queue->pop());
    }

    /**
     * @return void
     */
    public function testReportsProperlyIfQueueIsEmpty(): void
    {
        $queue = new JsonRpcQueue($this->requestPriorityDeterminer);

        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest(null, 'test'),
            $this->getMockBuilder(JsonRpcMessageSenderInterface::class)->getMock()
        );

        static::assertTrue($queue->isEmpty());

        $queue->push($queueItem, 1);

        static::assertFalse($queue->isEmpty());
    }

    /**
     * @return void
     */
    public function testReturnsCancelledQueueItemIfRequestWasCancelled(): void
    {
        $queue = new JsonRpcQueue($this->requestPriorityDeterminer);

        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', 'test'),
            $this->getMockBuilder(JsonRpcMessageSenderInterface::class)->getMock()
        );

        $queue->push($queueItem);
        $queue->cancel('theRequestId');

        static::assertTrue($queue->pop()->getIsCancelled());
    }
}
