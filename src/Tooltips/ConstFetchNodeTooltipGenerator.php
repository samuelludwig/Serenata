<?php

namespace Serenata\Tooltips;

use UnexpectedValueException;

use Serenata\Analysis\ConstantListProviderInterface;

use Serenata\Analysis\Node\ConstFetchNodeFqsenDeterminer;

use PhpParser\Node;

use Serenata\Common\Position;


use Serenata\Utility\TextDocumentItem;

/**
 * Provides tooltips for {@see Node\Expr\ConstFetch} nodes.
 */
class ConstFetchNodeTooltipGenerator
{
    /**
     * @var ConstantTooltipGenerator
     */
    private $constantTooltipGenerator;

    /**
     * @var ConstFetchNodeFqsenDeterminer
     */
    private $constFetchNodeFqsenDeterminer;

    /**
     * @var ConstantListProviderInterface
     */
    private $constantListProvider;

    /**
     * @param ConstantTooltipGenerator      $constantTooltipGenerator
     * @param ConstFetchNodeFqsenDeterminer  $constFetchNodeFqsenDeterminer
     * @param ConstantListProviderInterface $constantListProvider
     */
    public function __construct(
        ConstantTooltipGenerator $constantTooltipGenerator,
        ConstFetchNodeFqsenDeterminer $constFetchNodeFqsenDeterminer,
        ConstantListProviderInterface $constantListProvider
    ) {
        $this->constantTooltipGenerator = $constantTooltipGenerator;
        $this->constFetchNodeFqsenDeterminer = $constFetchNodeFqsenDeterminer;
        $this->constantListProvider = $constantListProvider;
    }

    /**
     * @param Node\Expr\ConstFetch $node
     * @param TextDocumentItem     $textDocumentItem
     * @param Position             $position
     *
     * @throws UnexpectedValueException when the constant was not found.
     *
     * @return string
     */
    public function generate(
        Node\Expr\ConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        $fqsen = $this->constFetchNodeFqsenDeterminer->determine(
            $node,
            $textDocumentItem,
            $position
        );

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
    private function getConstantInfo(string $fullyQualifiedName): array
    {
        $functions = $this->constantListProvider->getAll();

        if (!isset($functions[$fullyQualifiedName])) {
            throw new UnexpectedValueException('No data found for function with name ' . $fullyQualifiedName);
        }

        return $functions[$fullyQualifiedName];
    }
}
