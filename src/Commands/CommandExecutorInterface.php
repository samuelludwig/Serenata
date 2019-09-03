<?php

namespace Serenata\Commands;

use Serenata\Sockets\JsonRpcMessageInterface;

use Serenata\Utility\CommandInterface;

/**
 * Executes a(n LSP) command.
 */
interface CommandExecutorInterface
{
    /**
     * @param CommandInterface $command
     *
     * @return JsonRpcMessageInterface|null An optional message to send to the client.
     */
    public function execute(CommandInterface $command): ?JsonRpcMessageInterface;
}
