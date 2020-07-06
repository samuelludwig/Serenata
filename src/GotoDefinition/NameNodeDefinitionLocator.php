<?php

namespace Serenata\GotoDefinition;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\ClasslikeBuildingFailedException;

use Serenata\Analysis\Node\NameNodeFqsenDeterminer;

use Serenata\Common\Position;

use PhpParser\Node;

use Serenata\Tooltips\TooltipGenerationFailedException;

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
     * @throws TooltipGenerationFailedException when the constant was not found.
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
     * @throws TooltipGenerationFailedException
     *
     * @return array<string,mixed>
     */
    private function getClassLikeInfo(string $fullyQualifiedName): array
    {
        try {
            return $this->classLikeInfoBuilder->build($fullyQualifiedName);
        } catch (ClasslikeBuildingFailedException $e) {
            throw new TooltipGenerationFailedException(
                'Could not generate tooltip because classlike info could not be fetched',
                0,
                $e
            );
        }
    }
}
