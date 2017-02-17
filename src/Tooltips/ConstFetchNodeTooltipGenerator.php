<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

use PhpIntegrator\Analysis\ConstFetchNodeFqsenDeterminer;

use PhpParser\Node;

/**
 * Provides tooltips for {@see Node\Expr\ConstFetch} nodes.
 */
class ConstFetchNodeTooltipGenerator
{
    /**
     * @var ConstantTooltipGenerator
     */
    protected $constantTooltipGenerator;

    /**
     * @var ConstFetchNodeFqsenDeterminer
     */
    protected $constFetchNodeFqsenDeterminer;

    /**
     * @param ConstantTooltipGenerator      $constantTooltipGenerator
     * @param ConstFetchNodeFqsenDeterminer $constFetchNodeFqsenDeterminer
     */
    public function __construct(
        ConstantTooltipGenerator $constantTooltipGenerator,
        ConstFetchNodeFqsenDeterminer $constFetchNodeFqsenDeterminer
    ) {
        $this->constantTooltipGenerator = $constantTooltipGenerator;
        $this->constFetchNodeFqsenDeterminer = $constFetchNodeFqsenDeterminer;
    }

    /**
     * @param Node\Expr\ConstFetch $node
     *
     * @throws UnexpectedValueException when the function was not found.
     *
     * @return string
     */
    public function generate(Node\Expr\ConstFetch $node): string
    {
        $fqsen = $this->constFetchNodeFqsenDeterminer->determine($node);

        return $this->constantTooltipGenerator->generate($fqsen);
    }
}
