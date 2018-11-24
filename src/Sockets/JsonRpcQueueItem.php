<?php

namespace Serenata\Sockets;

/**
 * Queue item for the JSON RPC application queue.
 *
 * Value object.
 */
class JsonRpcQueueItem
{
    /**
     * @var JsonRpcRequest
     */
    private $request;

    /**
     * @var JsonRpcMessageSenderInterface
     */
    private $jsonRpcMessageSender;

    /**
     * @var bool
     */
    private $isCancelled;

    /**
     * @param JsonRpcRequest                 $request
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     * @param bool                           $isCancelled
     */
    public function __construct(
        JsonRpcRequest $request,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender,
        bool $isCancelled = false
    ) {
        $this->request = $request;
        $this->jsonRpcResponseSender = $jsonRpcMessageSender;
        $this->isCancelled = $isCancelled;
    }

    /**
     * @return JsonRpcRequest
     */
    public function getRequest(): JsonRpcRequest
    {
        return $this->request;
    }

    /**
     * @return JsonRpcMessageSenderInterface
     */
    public function getJsonRpcMessageSender(): JsonRpcMessageSenderInterface
    {
        return $this->jsonRpcResponseSender;
    }

    /**
     * @return bool
     */
    public function getIsCancelled(): bool
    {
        return $this->isCancelled;
    }
}
