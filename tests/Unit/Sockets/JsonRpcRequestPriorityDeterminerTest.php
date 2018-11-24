<?php

namespace Serenata\Tests\Unit\Sockets;

use PHPUnit\Framework\TestCase;

use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcQueueItemPriority;
use Serenata\Sockets\JsonRpcRequestPriorityDeterminer;

class JsonRpcRequestPriorityDeterminerTest extends TestCase
{
    /**
     * @return void
     */
    public function testAssignsNormalPriorityToNormalRequests(): void
    {
        $determiner = new JsonRpcRequestPriorityDeterminer();

        $request = new JsonRpcRequest(null, 'someGenericCommand');

        static::assertSame(JsonRpcQueueItemPriority::NORMAL, $determiner->determine($request));
    }

    /**
     * @return void
     */
    public function testAssignsCriticalPriorityToCancelRequestRequests(): void
    {
        $determiner = new JsonRpcRequestPriorityDeterminer();

        $request = new JsonRpcRequest(null, 'cancelRequest');

        static::assertSame(JsonRpcQueueItemPriority::CRITICAL, $determiner->determine($request));
    }

    /**
     * @return void
     */
    public function testAssignsLowPriorityToIndexRequests(): void
    {
        $determiner = new JsonRpcRequestPriorityDeterminer();

        $request = new JsonRpcRequest(null, 'index');

        static::assertSame(JsonRpcQueueItemPriority::LOW, $determiner->determine($request));
    }

    /**
     * @return void
     */
    public function testAssignsLowPriorityToIndexingProgressNotifications(): void
    {
        $determiner = new JsonRpcRequestPriorityDeterminer();

        $request = new JsonRpcRequest(null, 'echoMessage');

        static::assertSame(JsonRpcQueueItemPriority::LOW, $determiner->determine($request));
    }
}
