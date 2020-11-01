<?php

namespace Serenata\Sockets;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Sends JSON-RPC requests to the client.
 *
 * If you're handling requests sent by the client, this class is not what you want. Look at
 * {@see JsonRpcQueueItemProcessor} instead. This is for sending requests *to* the client, which is rarely needed.
 */
final class JsonRpcRequestSender
{
    /**
     * @var array<int|string,Deferred>
     */
    private $openRequests = [];

    /**
     * @param JsonRpcRequest                $request
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     *
     * @return PromiseInterface
     */
    public function send(JsonRpcRequest $request, JsonRpcMessageSenderInterface $jsonRpcMessageSender): PromiseInterface
    {
        $deferred = new Deferred();

        if ($request->getId() !== null) {
            $this->openRequests[$request->getId()] = $deferred;
        }

        $jsonRpcMessageSender->send($request);

        return $deferred->promise();
    }

    /**
     * @param JsonRpcResponse $jsonRpcResponse
     */
    public function handleResponse(JsonRpcResponse $jsonRpcResponse): void
    {
        if ($jsonRpcResponse->getId() === null) {
            return; // Wouldn't know what request this is for.
        } elseif (!isset($this->openRequests[$jsonRpcResponse->getId()])) {
            // TODO: Should probably log a warning to the client here.
            return; // Don't know this request.
        }

        $deferred = $this->openRequests[$jsonRpcResponse->getId()];

        if ($jsonRpcResponse->getError() === null) {
            $deferred->resolve($jsonRpcResponse->getResult());
        } else {
            $deferred->reject($jsonRpcResponse->getError()->getMessage());
        }

        unset($this->openRequests[$jsonRpcResponse->getId()]);
    }
}
