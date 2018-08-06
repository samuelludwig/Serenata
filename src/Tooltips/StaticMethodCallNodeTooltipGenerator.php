<?php

namespace Serenata\Tooltips;

use UnexpectedValueException;

use Serenata\Analysis\Node\MethodCallMethodInfoRetriever;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use PhpParser\Node;

use Serenata\Utility\TextDocumentItem;

/**
 * Provides tooltips for {@see Node\Expr\StaticCall} nodes.
 */
class StaticMethodCallNodeTooltipGenerator
{
    /**
     * @var MethodCallMethodInfoRetriever
     */
    private $methodCallMethodInfoRetriever;

    /**
     * @var FunctionTooltipGenerator
     */
    private $functionTooltipGenerator;

    /**
     * @param MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever
     * @param FunctionTooltipGenerator      $functionTooltipGenerator
     */
    public function __construct(
        MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever,
        FunctionTooltipGenerator $functionTooltipGenerator
    ) {
        $this->methodCallMethodInfoRetriever = $methodCallMethodInfoRetriever;
        $this->functionTooltipGenerator = $functionTooltipGenerator;
    }

    /**
     * @param Node\Expr\StaticCall $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    public function generate(Node\Expr\StaticCall $node, TextDocumentItem $textDocumentItem, Position $position): string
    {
        $infoElements = $this->methodCallMethodInfoRetriever->retrieve($node, $textDocumentItem, $position);

        if (empty($infoElements)) {
            throw new UnexpectedValueException('No method call information was found for node');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        $info = array_shift($infoElements);

        return $this->functionTooltipGenerator->generate($info);
    }
}
