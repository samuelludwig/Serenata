<?php

namespace PhpIntegrator\Sockets;

/**
 * Determines the appropriate priority for {@see JsonRpcRequest}s
 */
final class JsonRpcRequestPriorityDeterminer
{
    /**
     * @param JsonRpcRequest $request
     *
     * @return int
     */
    public function determine(JsonRpcRequest $request): int
    {
        if ($request->getMethod() === 'cancelRequest') {
            return JsonRpcQueueItemPriority::CRITICAL;
        }

        return JsonRpcQueueItemPriority::NORMAL;
    }
}
