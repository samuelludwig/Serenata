<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Throwable;

use React\Promise\ExtendedPromiseInterface;

use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * Interface for classes that handle queue items, which usually contain requests.
 */
interface JsonRpcQueueItemHandlerInterface
{
    /**
     * Handles the request by executing it.
     *
     * Returning a response is not required for handlers that only handle messages, such as notification handlers.
     * In other cases, you should either return the appropriate reponse to the handled request from this method or
     * send the response at a later time, which can be achieved by scheduling an echoMessage request in the queue
     * manually.
     *
     * Note that the return value should be a promise that eventually resolves either to a null value (don't send any
     * response) or the actual response. Unfortunately, the React Promise library does not implement PHPStan generics
     * yet, so the eventual type cannot be enforced by it.
     *
     * @param JsonRpcQueueItem $queueItem
     *
     * @see JsonRpcMessageInterface
     *
     * @throws Throwable                 when procesing the request fails.
     * @throws InvalidArgumentsException when the request is invalid or otherwise invalid arguments were passed.
     *
     * @return ExtendedPromiseInterface ExtendedPromiseInterface<JsonRpcMessageInterface|null>
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface;
}
