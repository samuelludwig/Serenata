<?php

namespace Serenata\Tooltips;

use UnexpectedValueException;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Node\NameNodeFqsenDeterminer;

use Serenata\Common\Position;


use PhpParser\Node;

use Serenata\Utility\TextDocumentItem;

/**
 * Provides tooltips for {@see Node\Name} nodes.
 */
final class NameNodeTooltipGenerator
{
    /**
     * @var ClassLikeTooltipGenerator
     */
    private $classLikeTooltipGenerator;

    /**
     * @var NameNodeFqsenDeterminer
     */
    private $nameNodeFqsenDeterminer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classLikeInfoBuilder;

    /**
     * @param ClassLikeTooltipGenerator     $classLikeTooltipGenerator
     * @param NameNodeFqsenDeterminer       $nameNodeFqsenDeterminer
     * @param ClasslikeInfoBuilderInterface $classLikeInfoBuilder
     */
    public function __construct(
        ClassLikeTooltipGenerator $classLikeTooltipGenerator,
        NameNodeFqsenDeterminer $nameNodeFqsenDeterminer,
        ClasslikeInfoBuilderInterface $classLikeInfoBuilder
    ) {
        $this->classLikeTooltipGenerator = $classLikeTooltipGenerator;
        $this->nameNodeFqsenDeterminer = $nameNodeFqsenDeterminer;
        $this->classLikeInfoBuilder = $classLikeInfoBuilder;
    }

    /**
     * @param Node\Name        $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UnexpectedValueException when the constant was not found.
     *
     * @return string
     */
    public function generate(
        Node\Name $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        $fqsen = $this->nameNodeFqsenDeterminer->determine($node, $textDocumentItem, $position);

        $info = $this->getClassLikeInfo($fqsen);

        return $this->classLikeTooltipGenerator->generate($info);
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    private function getClassLikeInfo(string $fullyQualifiedName): array
    {
        return $this->classLikeInfoBuilder->build($fullyQualifiedName);
    }
}
