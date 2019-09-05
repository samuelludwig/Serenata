<?php

namespace Serenata\Commands;

use DomainException;

use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcMessageInterface;

use Serenata\Utility\CommandInterface;

/**
 * Executes a {@see OpenTextDocumentCommand}.
 */
final class OpenTextDocumentCommandExecutor implements CommandExecutorInterface
{
    /**
     * @inheritDoc
     */
    public function execute(CommandInterface $command): ?JsonRpcMessageInterface
    {
        if (!$command instanceof OpenTextDocumentCommand) {
            throw new DomainException(
                'Only know how to handle instances of "' . OpenTextDocumentCommand::class . '", got "' .
                get_class($command) . '" instead'
            );
        }

        // This command is handled by just asking the client to open the document. This whole command could in theory
        // be handled by the client, without going past the server, but doing this anyway allows clients to maintain
        // the same flow for all commands and us to do additional processing here, if it is ever needed.
        return new JsonRpcRequest(null, ClientCommandName::OPEN_TEXT_DOCUMENT, $command->getArguments());
    }
}
