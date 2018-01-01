<?php

namespace PhpIntegrator\Sockets;

use Throwable;

use Ds\Vector;

use PhpIntegrator\Analysis\ClearableCacheInterface;

use PhpIntegrator\Indexing\ManagerRegistry;
use PhpIntegrator\Indexing\IncorrectDatabaseVersionException;

use PhpIntegrator\UserInterface\Command;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Processes {@see JsonRpcQueueItem}s.
 */
class JsonRpcQueueItemProcessor
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @param ContainerBuilder $container
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * @param JsonRpcQueueItem $queueItem
     */
    public function process(JsonRpcQueueItem $queueItem): void
    {
        $error = null;
        $response = null;

        if (!$queueItem->getIsCancelled()) {
            try {
                $response = $this->handle($queueItem);
            } catch (RequestParsingException $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $e->getMessage());
            } catch (Command\InvalidArgumentsException $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $e->getMessage());
            } catch (IncorrectDatabaseVersionException $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::DATABASE_VERSION_MISMATCH, $e->getMessage());
            } catch (\RuntimeException $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::GENERIC_RUNTIME_ERROR, $e->getMessage());
            } catch (\Throwable $e) {
                $error = new JsonRpcError(JsonRpcErrorCode::FATAL_SERVER_ERROR, $e->getMessage(), [
                    'line'      => $e->getLine(),
                    'file'      => $e->getFile(),
                    'backtrace' => $this->getCompleteBacktraceFromThrowable($e)
                ]);
            }
        } else {
            $error = new JsonRpcError(JsonRpcErrorCode::REQUEST_CANCELLED, 'Request was cancelled');
        }

        if ($error !== null) {
            $response = new JsonRpcResponse($queueItem->getRequest()->getId(), null, $error);
        }

        if ($response !== null) {
            $queueItem->getJsonRpcResponseSender()->send($response);
        }
    }

    /**
     * @param JsonRpcQueueItem $queueItem
     *
     * @throws RequestParsingException
     *
     * @return JsonRpcResponse|null
     */
    private function handle(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $params = $queueItem->getRequest()->getParams();

        // TODO: This should probably be handled by the commands proper at some point.
        if (isset($params['stdinData'])) {
            $this->container->get('stdinStream')->set($params['stdinData']);
        }

        if (isset($params['database'])) {
            $this->setDatabaseFile($params['database']);
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
            return $this->container->get($method . 'Command');
        } catch (ServiceNotFoundException $e) {
            throw new RequestParsingException('Method "' . $method . '" was not found');
        }

        throw new AssertionError('Should not be reached');
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
            if (!empty($carry)) {
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

    /**
     * @param string $databaseFile
     */
    private function setDatabaseFile(string $databaseFile): void
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->container->get('managerRegistry');

        if (!$managerRegistry->hasInitialDatabasePathConfigured() ||
            $managerRegistry->getDatabasePath() !== $databaseFile
        ) {
            $managerRegistry->setDatabasePath($databaseFile);

            /** @var ClearableCacheInterface $clearableCache */
            $clearableCache = $this->container->get('cacheClearingEventMediator.clearableCache');
            $clearableCache->clearCache();
        }
    }
}
