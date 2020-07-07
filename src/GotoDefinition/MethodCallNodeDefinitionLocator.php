<?php

namespace Serenata\GotoDefinition;

use PhpParser\Node;

use Serenata\Analysis\Node\MethodCallMethodInfoRetriever;

use Serenata\Common\Position;

use Serenata\Utility\Location;
use Serenata\Utility\TextDocumentItem;

/**
 * Locates the definition of the function called in {@see Node\Expr\MethodCall} nodes.
 */
final class MethodCallNodeDefinitionLocator
{
    /**
     * @var MethodCallMethodInfoRetriever
     */
    private $methodCallMethodInfoRetriever;

    /**
     * @param MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever
     */
    public function __construct(MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever)
    {
        $this->methodCallMethodInfoRetriever = $methodCallMethodInfoRetriever;
    }

    /**
     * @param Node\Expr\MethodCall $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws DefinitionLocationFailedException
     *
     * @return GotoDefinitionResponse
     */
    public function locate(
        Node\Expr\MethodCall $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResponse {
        $infoElements = $this->methodCallMethodInfoRetriever->retrieve($node, $textDocumentItem, $position);

        if (count($infoElements) === 0) {
            throw new DefinitionLocationFailedException('No method call information was found for node');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        $info = array_shift($infoElements);

        assert(
            $info !== null,
            'Null should never be returned on shifting as the error was already guaranteed to be not empty'
        );

        return new GotoDefinitionResponse(new Location($info['uri'], $info['range']));
    }
}
