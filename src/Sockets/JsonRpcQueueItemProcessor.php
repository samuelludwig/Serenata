<?php

namespace Serenata\Sockets;

use Throwable;
use DomainException;
use RuntimeException;
use UnexpectedValueException;

use Ds\Vector;

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
        $error = null;
        $message = null;

        if ($this->activeWorkspaceManager->getActiveWorkspace() === null &&
            $queueItem->getRequest()->getMethod() !== 'initialize'
        ) {
            $error = new JsonRpcError(
                JsonRpcErrorCode::SERVER_NOT_INITIALIZED,
                'Server not initialized yet, no active workspace'
            );
        } elseif (!$queueItem->getIsCancelled()) {
            try {
                $message = $this->handle($queueItem);
            } catch (RequestParsingException $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $e->getMessage());
            } catch (JsonRpcQueueItemHandler\InvalidArgumentsException $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $e->getMessage());
            } catch (IncorrectDatabaseVersionException $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::DATABASE_VERSION_MISMATCH, $e->getMessage());
            } catch (RuntimeException $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::GENERIC_RUNTIME_ERROR, $e->getMessage());
            } catch (Throwable $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::FATAL_SERVER_ERROR, $e->getMessage(), [
                    'line'      => $e->getLine(),
                    'file'      => $e->getFile(),
                    'backtrace' => $this->getCompleteBacktraceFromThrowable($e),
                ]);
            }
        } else {
            $error = new JsonRpcError(JsonRpcErrorCode::REQUEST_CANCELLED, 'Request was cancelled');
        }

        if ($error !== null) {
            $message = new JsonRpcResponse($queueItem->getRequest()->getId(), null, $error);
        }

        if ($message !== null) {
            $queueItem->getJsonRpcMessageSender()->send($message);
        }
    }

    /**
     * @param JsonRpcQueueItem $queueItem
     *
     * @throws RequestParsingException
     * @throws InvalidArgumentsException
     * @throws Throwable
     *
     * @return JsonRpcMessageInterface|null
     */
    private function handle(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $params = $queueItem->getRequest()->getParams();

        // TODO: This should probably be handled by the commands proper at some point.
        if (isset($params['stdinData'])) {
            $this->stdinStream->set($params['stdinData']);
        }

        try {
            $handler = $this->jsonRpcQueueItemHandlerFactory->create($queueItem->getRequest()->getMethod());
        } catch (DomainException $e) {
            throw new UnexpectedValueException(
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
     * @return Vector
     */
    private function getThrowableVector(Throwable $throwable): Vector
    {
        $vector = new Vector();

        $item = $throwable;

        while ($item) {
            $vector[] = $item;

            $item = $item->getPrevious();
        }

        return $vector;
    }
}
