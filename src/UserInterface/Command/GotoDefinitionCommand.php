<?php

namespace Serenata\UserInterface\Command;

use Serenata\Common\Position;

use Serenata\GotoDefinition\DefinitionLocator;
use Serenata\GotoDefinition\GotoDefinitionResponse;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\TextDocumentItem;
use Serenata\Utility\SourceCodeStreamReader;

/**
 * Allows navigating to the definition of a structural element by returning the location of its definition.
 */
final class GotoDefinitionCommand extends AbstractCommand
{
    /**
     * @var DefinitionLocator
     */
    private $definitionLocator;

    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @param DefinitionLocator      $definitionLocator
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     */
    public function __construct(DefinitionLocator $definitionLocator, SourceCodeStreamReader $sourceCodeStreamReader)
    {
        $this->definitionLocator = $definitionLocator;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['uri'])) {
            throw new InvalidArgumentsException('"uri" must be supplied');
        } elseif (!isset($arguments['position'])) {
            throw new InvalidArgumentsException('"position" into the source must be supplied');
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['uri']);
        }

        $position = new Position($arguments['position']['line'], $arguments['position']['character']);

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->gotoDefinition($arguments['uri'], $code, $position)
        );
    }

    /**
     * @param string   $uri
     * @param string   $code
     * @param Position $position
     *
     * @return GotoDefinitionResponse|null
     */
    public function gotoDefinition(string $uri, string $code, Position $position): ?GotoDefinitionResponse
    {
        return $this->definitionLocator->locate(new TextDocumentItem($uri, $code), $position);
    }
}
