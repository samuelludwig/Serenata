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
     * @param JsonRpcRequest                 $request
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     */
    public function __construct(JsonRpcRequest $request, JsonRpcResponseSenderInterface $jsonRpcResponseSender)
    {
        $this->request = $request;
        $this->jsonRpcResponseSender = $jsonRpcResponseSender;
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
}
