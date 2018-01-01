<?php

namespace PhpIntegrator\Sockets;

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
     * @var JsonRpcResponseSenderInterface
     */
    private $jsonRpcResponseSender;

    /**
     * @var bool
     */
    private $isCancelled;

    /**
     * @param JsonRpcRequest                 $request
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param bool                           $isCancelled
     */
    public function __construct(
        JsonRpcRequest $request,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        bool $isCancelled = false
    ) {
        $this->request = $request;
        $this->jsonRpcResponseSender = $jsonRpcResponseSender;
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
     * @return JsonRpcResponseSenderInterface
     */
    public function getJsonRpcResponseSender(): JsonRpcResponseSenderInterface
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
