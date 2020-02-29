<?php

namespace Serenata\Utility;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

/**
 * Convenience class that aids in logging messages to the client (via the window/logMessage notification).
 */
final class MessageLogger
{
    /**
     * @var JsonRpcQueue
     */
    private $queue;

    /**
     * @param JsonRpcQueue $queue
     */
    public function __construct(JsonRpcQueue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @param LogMessageParams              $logMessageParams
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     */
    public function log(LogMessageParams $logMessageParams, JsonRpcMessageSenderInterface $jsonRpcMessageSender): void
    {
        $request = new JsonRpcRequest(null, 'serenata/internal/echoMessage', [
            'message' => new JsonRpcRequest(
                null,
                'window/logMessage',
                $logMessageParams->jsonSerialize()
            ),
        ]);

        $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));
    }
}
