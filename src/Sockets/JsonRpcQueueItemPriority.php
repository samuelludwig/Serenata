<?php

namespace PhpIntegrator\Sockets;

/**
 * Enumeration of priorities for {@see JsonRpcQueueItem}s.
 */
final class JsonRpcQueueItemPriority
{
    /**
     * @var int
     */
    public const LOW = 0;

    /**
     * @var int
     */
    public const NORMAL = 1;

    /**
     * @var int
     */
    public const HIGH = 2;

    /**
     * @var int
     */
    public const CRITICAL = 3;
}
