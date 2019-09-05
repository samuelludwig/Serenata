<?php

namespace Serenata\GotoDefinition;

use UnexpectedValueException;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Node\NameNodeFqsenDeterminer;

use Serenata\Common\Position;


use PhpParser\Node;

use Serenata\Utility\Location;
use Serenata\Utility\TextDocumentItem;

/**
 * Locates the definition of classlikes represented by {@see Node\Name} nodes.
 */
final class NameNodeDefinitionLocator
{
    /**
     * @var NameNodeFqsenDeterminer
     */
    private $nameNodeFqsenDeterminer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classLikeInfoBuilder;

    /**
     * @param NameNodeFqsenDeterminer       $nameNodeFqsenDeterminer
     * @param ClasslikeInfoBuilderInterface $classLikeInfoBuilder
     */
    public function __construct(
        NameNodeFqsenDeterminer $nameNodeFqsenDeterminer,
        ClasslikeInfoBuilderInterface $classLikeInfoBuilder
    ) {
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
     * @return GotoDefinitionResponse
     */
    public function locate(
        Node\Name $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResponse {
        $fqsen = $this->nameNodeFqsenDeterminer->determine($node, $textDocumentItem, $position);

        $info = $this->getClassLikeInfo($fqsen);

        return new GotoDefinitionResponse(new Location($info['uri'], $info['range']));
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
