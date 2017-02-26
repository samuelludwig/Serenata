<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

use PhpIntegrator\Analysis\GlobalConstantsProvider;
use PhpIntegrator\Analysis\ConstNameNodeFqsenDeterminer;

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
     * @var ConstNameNodeFqsenDeterminer
     */
    protected $constFetchNodeFqsenDeterminer;

    /**
     * @var GlobalConstantsProvider
     */
    protected $globalConstantsProvider;

    /**
     * @param ConstantTooltipGenerator      $constantTooltipGenerator
     * @param ConstNameNodeFqsenDeterminer $constFetchNodeFqsenDeterminer
     * @param GlobalConstantsProvider       $globalConstantsProvider
     */
    public function __construct(
        ConstantTooltipGenerator $constantTooltipGenerator,
        ConstNameNodeFqsenDeterminer $constFetchNodeFqsenDeterminer,
        GlobalConstantsProvider $globalConstantsProvider
    ) {
        $this->constantTooltipGenerator = $constantTooltipGenerator;
        $this->constFetchNodeFqsenDeterminer = $constFetchNodeFqsenDeterminer;
        $this->globalConstantsProvider = $globalConstantsProvider;
    }

    /**
     * @param Node\Expr\ConstFetch $node
     *
     * @throws UnexpectedValueException when the constant was not found.
     *
     * @return string
     */
    public function generate(Node\Expr\ConstFetch $node): string
    {
        $fqsen = $this->constFetchNodeFqsenDeterminer->determine($node->name);

        $info = $this->getConstantInfo($fqsen);

        return $this->constantTooltipGenerator->generate($info);
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    protected function getConstantInfo(string $fullyQualifiedName): array
    {
        $functions = $this->globalConstantsProvider->getAll();

        if (!isset($functions[$fullyQualifiedName])) {
            throw new UnexpectedValueException('No data found for function with name ' . $fullyQualifiedName);
        }

        return $functions[$fullyQualifiedName];
    }
}
