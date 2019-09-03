<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Commands\CommandFactory;
use Serenata\Commands\CommandExecutorFactory;
use Serenata\Commands\BadCommandArgumentsException;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

/**
 * Handler that executes a command requested by the client.
 */
final class ExecuteCommandJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var CommandExecutorFactory
     */
    private $commandExecutorFactory;

    /**
     * @var CommandFactory
     */
    private $commandFactory;

    /**
     * @var JsonRpcQueue
     */
    private $queue;

    /**
     * @param CommandExecutorFactory $commandExecutorFactory
     * @param CommandFactory         $commandFactory
     * @param JsonRpcQueue           $queue
     */
    public function __construct(
        CommandExecutorFactory $commandExecutorFactory,
        CommandFactory $commandFactory,
        JsonRpcQueue $queue
    ) {
        $this->commandExecutorFactory = $commandExecutorFactory;
        $this->commandFactory = $commandFactory;
        $this->queue = $queue;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $parameters = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($parameters['command'])) {
            throw new InvalidArgumentsException('"command" must be supplied');
        }

        try {
            $command = $this->commandFactory->create($parameters['command'], $parameters['arguments'] ?? null);
        } catch (BadCommandArgumentsException $e) {
            throw new InvalidArgumentsException('Request to execute command invalid, see previous exception', 0, $e);
        }

        $message = $this->commandExecutorFactory->create($command)->execute($command);

        if ($message !== null) {
            $this->queue->push(new JsonRpcQueueItem(
                new JsonRpcRequest(null, 'serenata/internal/echoMessage', [
                    'message' => $message,
                ]),
                $queueItem->getJsonRpcMessageSender()
            ));
        }

        return new JsonRpcResponse($queueItem->getRequest()->getId(), null);
    }
}
