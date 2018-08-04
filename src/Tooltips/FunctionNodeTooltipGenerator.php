<?php

namespace Serenata\Tooltips;

use UnexpectedValueException;

use Serenata\Analysis\Node\FunctionFunctionInfoRetriever;

use PhpParser\Node;

use Serenata\Indexing\Structures;

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
     *
     * @throws UnexpectedValueException when the function was not found.
     *
     * @return string
     */
    public function generate(
        Node\Stmt\Function_ $node,
        Structures\File $file,
        string $code,
        int $offset
    ): string {
        $info = $this->functionFunctionInfoRetriever->retrieve($node, $file, $code, $offset);

        return $this->functionTooltipGenerator->generate($info);
    }
}
