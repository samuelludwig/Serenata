<?php

namespace Serenata\Tests\Unit\Sockets;

use PHPUnit\Framework\TestCase;

use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcQueueItemPriority;
use Serenata\Sockets\JsonRpcRequestPriorityDeterminer;

final class JsonRpcRequestPriorityDeterminerTest extends TestCase
{
    /**
     * @return void
     */
    public function testAssignsNormalPriorityToNormalRequests(): void
    {
        $determiner = new JsonRpcRequestPriorityDeterminer();

        $request = new JsonRpcRequest(null, 'someGenericJsonRpcQueueItemHandler');

        self::assertSame(JsonRpcQueueItemPriority::NORMAL, $determiner->determine($request));
    }

    /**
     * @return void
     */
    public function testAssignsCriticalPriorityToCancelRequestRequests(): void
    {
        $determiner = new JsonRpcRequestPriorityDeterminer();

        $request = new JsonRpcRequest(null, '$/cancelRequest');

        self::assertSame(JsonRpcQueueItemPriority::CRITICAL, $determiner->determine($request));
    }

    /**
     * @return void
     */
    public function testAssignsLowPriorityToIndexRequests(): void
    {
        $determiner = new JsonRpcRequestPriorityDeterminer();

        $request = new JsonRpcRequest(null, 'serenata/internal/index');

        self::assertSame(JsonRpcQueueItemPriority::LOW, $determiner->determine($request));
    }

    /**
     * @return void
     */
    public function testAssignsLowPriorityToIndexingProgressNotifications(): void
    {
        $determiner = new JsonRpcRequestPriorityDeterminer();

        $request = new JsonRpcRequest(null, 'serenata/internal/echoMessage');

        self::assertSame(JsonRpcQueueItemPriority::LOW, $determiner->determine($request));
    }
}
