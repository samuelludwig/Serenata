<?php

namespace PhpIntegrator\Sockets;

/**
 * Interface for {@see JsonRpcRequest} handlers.
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
