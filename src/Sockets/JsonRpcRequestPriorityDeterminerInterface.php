<?php

namespace PhpIntegrator\Sockets;

/**
 * Interface for classes that determine the appropriate priority for {@see JsonRpcRequest}s
 */
interface JsonRpcRequestPriorityDeterminerInterface
{
    /**
     * @param JsonRpcRequest $request
     *
     * @return int
     */
    public function determine(JsonRpcRequest $request): int;
}
