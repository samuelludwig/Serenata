<?php

namespace Serenata\GotoDefinition;

use UnexpectedValueException;

use PhpParser\Node;

use Serenata\Analysis\Node\MethodCallMethodInfoRetriever;

use Serenata\Common\Position;

use Serenata\Utility\TextDocumentItem;

/**
 * Locates the definition of the function called in {@see Node\Expr\StaticCall} nodes.
 */
final class StaticMethodCallNodeDefinitionLocator
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
     * @param Node\Expr\StaticCall $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    public function locate(
        Node\Expr\StaticCall $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        $infoElements = $this->methodCallMethodInfoRetriever->retrieve($node, $textDocumentItem, $position);

        if (empty($infoElements)) {
            throw new UnexpectedValueException('No method call information was found for node');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        $info = array_shift($infoElements);

        return new GotoDefinitionResult($info['filename'], $info['startLine']);
    }
}
