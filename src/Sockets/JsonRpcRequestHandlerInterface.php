<?php

namespace PhpIntegrator\Sockets;

/**
 * Interface for {@see JsonRpcRequestHandler} handlers.
 */
interface JsonRpcRequestHandlerInterface
{
    /**
     * @param JsonRpcRequest $request
     *
     * @return JsonRpcResponse
     */
    public function handle(JsonRpcRequest $request);
}
