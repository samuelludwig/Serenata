<?php

namespace Serenata\Tooltips;

use UnexpectedValueException;

use Serenata\Analysis\Node\FunctionFunctionInfoRetriever;

use PhpParser\Node;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Utility\TextDocumentItem;

/**
 * Provides tooltips for {@see Node\Stmt\Function_} nodes.
 */
class FunctionNodeTooltipGenerator
{
    /**
     * @var FunctionTooltipGenerator
     */
    private $functionTooltipGenerator;

    /**
     * @var FunctionFunctionInfoRetriever
     */
    private $functionFunctionInfoRetriever;

    /**
     * @param FunctionTooltipGenerator      $functionTooltipGenerator
     * @param FunctionFunctionInfoRetriever $functionFunctionInfoRetriever
     */
    public function __construct(
        FunctionTooltipGenerator $functionTooltipGenerator,
        FunctionFunctionInfoRetriever $functionFunctionInfoRetriever
    ) {
        $this->functionTooltipGenerator = $functionTooltipGenerator;
        $this->functionFunctionInfoRetriever = $functionFunctionInfoRetriever;
    }

    /**
     * @param Node\Stmt\Function_ $node
     * @param TextDocumentItem    $textDocumentItem
     * @param Position            $position
     *
     * @throws UnexpectedValueException when the function was not found.
     *
     * @return string
     */
    public function generate(
        Node\Stmt\Function_ $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        $info = $this->functionFunctionInfoRetriever->retrieve($node, $textDocumentItem, $position);

        return $this->functionTooltipGenerator->generate($info);
    }
}
