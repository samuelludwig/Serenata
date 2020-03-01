<?php

namespace Serenata\Sockets;

use Throwable;
use DomainException;
use RuntimeException;

use Ds\Vector;

use React\Promise\ExtendedPromiseInterface;

use Serenata\Indexing\IncorrectDatabaseVersionException;

use Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\UserInterface\JsonRpcQueueItemHandler\InvalidArgumentsException;

use Serenata\UserInterface\JsonRpcQueueItemHandlerFactoryInterface;

use Serenata\Utility\StreamInterface;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * Processes {@see JsonRpcQueueItem}s.
 */
final class JsonRpcQueueItemProcessor
{
    /**
     * @var JsonRpcQueueItemHandlerFactoryInterface
     */
    private $jsonRpcQueueItemHandlerFactory;

    /**
     * @var StreamInterface
     */
    private $stdinStream;

    /**
     * @var ActiveWorkspaceManager
     */
    private $activeWorkspaceManager;

    /**
     * @param JsonRpcQueueItemHandlerFactoryInterface $jsonRpcQueueItemHandlerFactory
     * @param StreamInterface                         $stdinStream
     * @param ActiveWorkspaceManager                  $activeWorkspaceManager
     */
    public function __construct(
        JsonRpcQueueItemHandlerFactoryInterface $jsonRpcQueueItemHandlerFactory,
        StreamInterface $stdinStream,
        ActiveWorkspaceManager $activeWorkspaceManager
    ) {
        $this->jsonRpcQueueItemHandlerFactory = $jsonRpcQueueItemHandlerFactory;
        $this->stdinStream = $stdinStream;
        $this->activeWorkspaceManager = $activeWorkspaceManager;
    }

    /**
     * @param JsonRpcQueueItem $queueItem
     */
    public function process(JsonRpcQueueItem $queueItem): void
    {
        if ($this->activeWorkspaceManager->getActiveWorkspace() === null &&
            $queueItem->getRequest()->getMethod() !== 'initialize'
        ) {
            $queueItem->getJsonRpcMessageSender()->send(
                new JsonRpcResponse($queueItem->getRequest()->getId(), null, new JsonRpcError(
                    JsonRpcErrorCode::SERVER_NOT_INITIALIZED,
                    'Server not initialized yet, no active workspace'
                ))
            );

            return;
        }

        if ($queueItem->getIsCancelled()) {
            $queueItem->getJsonRpcMessageSender()->send(
                new JsonRpcResponse($queueItem->getRequest()->getId(), null, new JsonRpcError(
                    JsonRpcErrorCode::REQUEST_CANCELLED,
                    'Request was cancelled'
                ))
            );

            return;
        }

        $onFulfilled = function (?JsonRpcMessageInterface $message) use ($queueItem): void {
            if ($message !== null) {
                $queueItem->getJsonRpcMessageSender()->send($message);
            }
        };

        $onRejected = function (Throwable $throwable) use ($queueItem): void {
            $queueItem->getJsonRpcMessageSender()->send(new JsonRpcResponse(
                $queueItem->getRequest()->getId(),
                null,
                $this->convertExceptionToJsonRpcError($throwable)
            ));
        };

        try {
            $this->handle($queueItem)->then($onFulfilled, $onRejected);
        } catch (Throwable $throwable) {
            $onRejected($throwable);
        }
    }

    /**
     * @param Throwable $throwable
     *
     * @return JsonRpcError
     */
    private function convertExceptionToJsonRpcError(Throwable $throwable): JsonRpcError
    {
        $data = [
            'line'      => $throwable->getLine(),
            'file'      => $throwable->getFile(),
            'backtrace' => $this->getCompleteBacktraceFromThrowable($throwable),
        ];

        if ($throwable instanceof UnknownJsonRpcRequestMethodException) {
            return new JsonRpcError(JsonRpcErrorCode::METHOD_NOT_FOUND, $throwable->getMessage(), $data);
        } elseif ($throwable instanceof RequestParsingException) {
            return new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $throwable->getMessage(), $data);
        } elseif ($throwable instanceof JsonRpcQueueItemHandler\InvalidArgumentsException) {
            return new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $throwable->getMessage(), $data);
        } elseif ($throwable instanceof IncorrectDatabaseVersionException) {
            return new JsonRpcError(JsonRpcErrorCode::DATABASE_VERSION_MISMATCH, $throwable->getMessage(), $data);
        } elseif ($throwable instanceof RuntimeException) {
            return new JsonRpcError(JsonRpcErrorCode::GENERIC_RUNTIME_ERROR, $throwable->getMessage(), $data);
        }

        return new JsonRpcError(JsonRpcErrorCode::FATAL_SERVER_ERROR, $throwable->getMessage(), $data);
    }

    /**
     * @param JsonRpcQueueItem $queueItem
     *
     * @throws RequestParsingException
     * @throws InvalidArgumentsException
     * @throws UnknownJsonRpcRequestMethodException
     * @throws Throwable
     *
     * @return ExtendedPromiseInterface ExtendedPromiseInterface<JsonRpcMessageInterface|null>
     */
    private function handle(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $params = $queueItem->getRequest()->getParams();

        // TODO: This should probably be handled by the commands proper at some point.
        if (isset($params['stdinData'])) {
            $this->stdinStream->set($params['stdinData']);
        }

        try {
            $handler = $this->jsonRpcQueueItemHandlerFactory->create($queueItem->getRequest()->getMethod());
        } catch (DomainException $e) {
            throw new UnknownJsonRpcRequestMethodException(
                'Unknown request method "' . $queueItem->getRequest()->getMethod() . '"',
                0,
                $e
            );
        }

        return $handler->execute($queueItem);
    }

    /**
     * @param Throwable $throwable
     *
     * @return string
     */
    private function getCompleteBacktraceFromThrowable(Throwable $throwable): string
    {
        $counter = 1;

        $reducer = function (string $carry, Throwable $item) use (&$counter): string {
            if ($carry !== '') {
                $carry .= "\n \n";
            }

            $carry .= "→ Message {$counter}\n";
            $carry .= $item->getMessage() . "\n \n";

            $carry .= "→ Location {$counter}\n";
            $carry .= $item->getFile() . ':' . $item->getLine() . "\n \n";

            $carry .= "→ Backtrace {$counter}\n";
            $carry .= $item->getTraceAsString();

            ++$counter;

            return $carry;
        };

        return $this->getThrowableVector($throwable)->reduce($reducer, '');
    }

    /**
     * @param Throwable $throwable
     *
     * @return Vector<Throwable>
     */
    private function getThrowableVector(Throwable $throwable): Vector
    {
        $vector = new Vector();

        $item = $throwable;

        while ($item) {
            $vector->push($item);

            $item = $item->getPrevious();
        }

        return $vector;
    }
}
