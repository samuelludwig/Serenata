<?php

namespace Serenata\Commands;

use DomainException;

use Serenata\Utility\CommandInterface;

/**
 * Executes a(n LSP) command.
 */
final class CommandExecutorFactory
{
    /**
     * @var CommandExecutorInterface
     */
    private $openTextDocumentCommandExecutor;

    /**
     * @param CommandExecutorInterface $openTextDocumentCommandExecutor
     */
    public function __construct(CommandExecutorInterface $openTextDocumentCommandExecutor)
    {
        $this->openTextDocumentCommandExecutor = $openTextDocumentCommandExecutor;
    }

    /**
     * @param CommandInterface $command
     *
     * @return CommandExecutorInterface
     */
    public function create(CommandInterface $command): CommandExecutorInterface
    {
        if ($command->getCommand() === OpenTextDocumentCommand::getCommandName()) {
            return $this->openTextDocumentCommandExecutor;
        }

        throw new DomainException(
            'Don\'t know any class that can handle commands of type "' . get_class($command) . '"'
        );
    }
}
