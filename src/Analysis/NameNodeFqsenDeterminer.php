<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;

/**
 * Determines the FQSEN of a name used in a name node.
 */
class NameNodeFqsenDeterminer
{
    /**
     * @var FileTypeResolverFactoryInterface
     */
    protected $fileTypeResolverFactory;

    /**
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     */
    public function __construct(FileTypeResolverFactoryInterface $fileTypeResolverFactory)
    {
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
    }

    /**
     * @param Node\Name $node
     * @param string    $file
     * @param int       $line
     *
     * @return string
     */
    public function determine(Node\Name $node, string $file, int $line): string
    {
        $fileTypeResolver = $this->fileTypeResolverFactory->create($file);

        $type = NodeHelpers::fetchClassName($node);

        return $fileTypeResolver->resolve($type, $line, UseStatementKind::TYPE_CLASSLIKE);
    }
}
