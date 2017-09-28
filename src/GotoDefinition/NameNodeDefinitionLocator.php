<?php

namespace PhpIntegrator\GotoDefinition;

use UnexpectedValueException;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Node\NameNodeFqsenDeterminer;

use PhpIntegrator\Indexing\Structures;

use PhpParser\Node;

/**
 * Locates the definition of classlikes represented by {@see Node\Name} nodes.
 */
class NameNodeDefinitionLocator
{
    /**
     * @var NameNodeFqsenDeterminer
     */
    private $nameNodeFqsenDeterminer;

    /**
     * @var ClasslikeInfoBuilder
     */
    private $classLikeInfoBuilder;

    /**
     * @param NameNodeFqsenDeterminer   $nameNodeFqsenDeterminer
     * @param ClasslikeInfoBuilder      $classLikeInfoBuilder
     */
    public function __construct(
        NameNodeFqsenDeterminer $nameNodeFqsenDeterminer,
        ClasslikeInfoBuilder $classLikeInfoBuilder
    ) {
        $this->nameNodeFqsenDeterminer = $nameNodeFqsenDeterminer;
        $this->classLikeInfoBuilder = $classLikeInfoBuilder;
    }

    /**
     * @param Node\Name       $node
     * @param Structures\File $file
     * @param int             $line
     *
     * @throws UnexpectedValueException when the constant was not found.
     *
     * @return GotoDefinitionResult
     */
    public function locate(Node\Name $node, Structures\File $file, int $line): GotoDefinitionResult
    {
        $fqsen = $this->nameNodeFqsenDeterminer->determine($node, $file, $line);

        $info = $this->getClassLikeInfo($fqsen);

        return new GotoDefinitionResult($info['filename'], $info['startLine']);
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    protected function getClassLikeInfo(string $fullyQualifiedName): array
    {
        return $this->classLikeInfoBuilder->getClasslikeInfo($fullyQualifiedName);
    }
}
