<?php

namespace Serenata\Analysis\Node;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Indexing\Structures;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Utility\NodeHelpers;

use PhpParser\Node;

/**
 * Determines the FQSEN of a name used in a name node.
 */
class NameNodeFqsenDeterminer
{
    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     */
    public function __construct(StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory)
    {
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
    }

    /**
     * @param Node\Name       $node
     * @param Structures\File $file
     * @param int             $line
     *
     * @return string
     */
    public function determine(Node\Name $node, Structures\File $file, int $line): string
    {
        $filePosition = new FilePosition($file->getPath(), new Position($line, 0));

        $fileTypeResolver = $this->structureAwareNameResolverFactory->create($filePosition);

        $type = NodeHelpers::fetchClassName($node);

        return $fileTypeResolver->resolve($type, $filePosition, UseStatementKind::TYPE_CLASSLIKE);
    }
}
