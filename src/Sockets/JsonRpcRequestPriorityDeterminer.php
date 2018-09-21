<?php

namespace Serenata\Sockets;

/**
 * Determines the appropriate priority for {@see JsonRpcRequest}s
 */
final class JsonRpcRequestPriorityDeterminer implements JsonRpcRequestPriorityDeterminerInterface
{
    /**
     * @param JsonRpcRequest $request
     *
     * @return int
     */
    public function determine(JsonRpcRequest $request): int
    {
        return $this->determineForRequestMethodName($request->getMethod());
    }

    /**
     * @param string $name
     *
     * @return int
     */
    private function determineForRequestMethodName(string $name): int
    {
        if ($name === 'cancelRequest') {
            return JsonRpcQueueItemPriority::CRITICAL;
        } elseif ($name === 'workspace/didChangeWatchedFilesCommand') {
            return JsonRpcQueueItemPriority::LOW;
        } elseif ($name === 'textDocument/didChange') {
            return JsonRpcQueueItemPriority::LOW;
        } elseif ($name === 'echoResponse') {
            // Responses should never be sent sooner or much later than their matching reindex request as they notify
            // of progress. Lower priority would mean all reindex requests are processed before notifications are sent
            // and higher would mean the other way around. This is not that great a solution as echoResponse could
            // theoretically also be used by other commands.
            // FIXME: Requests should have a settable priority so the one scheduling these notifications can assign the
            // same priority as the original request - or it just needs to be rewritten to schedule these with a higher
            // priority, but only once the original request finishes.
            return $this->determineForRequestMethodName('textDocument/didChange');
        }

        return JsonRpcQueueItemPriority::NORMAL;
    }
}
