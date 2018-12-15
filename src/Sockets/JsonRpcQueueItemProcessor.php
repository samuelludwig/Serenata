<?php

namespace Serenata\Sockets;

use Throwable;
use RuntimeException;

use Ds\Vector;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use Serenata\Indexing\IncorrectDatabaseVersionException;

use Serenata\UserInterface\Command;

use Serenata\UserInterface\Command\InvalidArgumentsException;

use Serenata\Workspace\ActiveWorkspaceManager;

/**
 * Processes {@see JsonRpcQueueItem}s.
 */
class JsonRpcQueueItemProcessor
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ActiveWorkspaceManager
     */
    private $activeWorkspaceManager;

    /**
     * @param ContainerInterface     $container
     * @param ActiveWorkspaceManager $activeWorkspaceManager
     */
    public function __construct(ContainerInterface $container, ActiveWorkspaceManager $activeWorkspaceManager)
    {
        $this->container = $container;
        $this->activeWorkspaceManager = $activeWorkspaceManager;
    }

    /**
     * @param JsonRpcQueueItem $queueItem
     */
    public function process(JsonRpcQueueItem $queueItem): void
    {
        $error = null;
        $message = null;

        if (!$this->activeWorkspaceManager->getActiveWorkspace() &&
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
            } catch (Command\InvalidArgumentsException $e) {
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
            $this->container->get('stdinStream')->set($params['stdinData']);
        }

        return $this->getCommandByMethod($queueItem->getRequest()->getMethod())->execute($queueItem);
    }

    /**
     * @param string $method
     *
     * @throws RequestParsingException
     *
     * @return Command\CommandInterface
     */
    private function getCommandByMethod(string $method): Command\CommandInterface
    {
        try {
            // TODO: Make a map of request names to service names and create a class that retrieves the appropriate
            // handler for a request method that uses it.
            // TODO: Rename "Command" to "RequestHandler" or similar.
            if ($method === 'workspace/didChangeWatchedFiles') {
                return $this->container->get('didChangeWatchedFilesCommand');
            } elseif ($method === 'textDocument/didChange') {
                return $this->container->get('didChangeCommand');
            } elseif ($method === 'textDocument/didSave') {
                return $this->container->get('didSaveCommand');
            } elseif ($method === 'textDocument/completion') {
                return $this->container->get('completionCommand');
            } elseif ($method === 'textDocument/hover') {
                return $this->container->get('hoverCommand');
            } elseif ($method === 'textDocument/definition') {
                return $this->container->get('definitionCommand');
            } elseif ($method === 'textDocument/signatureHelp') {
                return $this->container->get('signatureHelpCommand');
            } elseif ($method === 'textDocument/documentSymbol') {
                return $this->container->get('documentSymbolCommand');
            } elseif ($method === 'serenata/deprecated/getClassInfo') {
                return $this->container->get('classInfoCommand');
            } elseif ($method === 'serenata/deprecated/getClassListForFile') {
                return $this->container->get('classListCommand');
            } elseif ($method === 'serenata/deprecated/deduceTypes') {
                return $this->container->get('deduceTypesCommand');
            } elseif ($method === 'serenata/deprecated/getGlobalConstants') {
                return $this->container->get('globalConstantsCommand');
            } elseif ($method === 'serenata/deprecated/getGlobalFunctions') {
                return $this->container->get('globalFunctionsCommand');
            } elseif ($method === 'serenata/deprecated/resolveType') {
                return $this->container->get('resolveTypeCommand');
            } elseif ($method === 'serenata/deprecated/localizeType') {
                return $this->container->get('localizeTypeCommand');
            } elseif ($method === '$/cancelRequest') {
                return $this->container->get('cancelRequestCommand');
            }

            // TODO: This should be disabled, we don't want anyone accessing internal commands or requests.
            return $this->container->get($method . 'Command');
        } catch (NotFoundExceptionInterface $e) {
            throw new RequestParsingException('Method "' . $method . '" was not found');
        }
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
