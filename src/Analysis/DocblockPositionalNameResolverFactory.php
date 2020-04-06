<?php

namespace Serenata\Analysis;

use Serenata\Analysis\Typing\TypeAnalyzer;

use Serenata\Common\FilePosition;

use Serenata\NameQualificationUtilities\PositionalNameResolverInterface;
use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Parsing\DocblockTypeParserInterface;
use Serenata\Parsing\DocblockTypeTransformerInterface;

/**
 * Factory that creates instances of a {@see DocblockPositionalNameResolver}.
 */
final class DocblockPositionalNameResolverFactory implements StructureAwareNameResolverFactoryInterface
{
    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $delegate;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * @var DocblockTypeTransformerInterface
     */
    private $docblockTypeTransformer;

    /**
     * @param StructureAwareNameResolverFactoryInterface $delegate
     * @param TypeAnalyzer                               $typeAnalyzer
     * @param DocblockTypeParserInterface                $docblockTypeParser
     * @param DocblockTypeTransformerInterface           $docblockTypeTransformer
     */
    public function __construct(
        StructureAwareNameResolverFactoryInterface $delegate,
        TypeAnalyzer $typeAnalyzer,
        DocblockTypeParserInterface $docblockTypeParser,
        DocblockTypeTransformerInterface $docblockTypeTransformer
    ) {
        $this->delegate = $delegate;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockTypeParser = $docblockTypeParser;
        $this->docblockTypeTransformer = $docblockTypeTransformer;
    }

    /**
     * @inheritDoc
     */
    public function create(FilePosition $filePosition): PositionalNameResolverInterface
    {
        $delegate = $this->delegate->create($filePosition);

        return new DocblockPositionalNameResolver(
            $delegate,
            $this->typeAnalyzer,
            $this->docblockTypeParser,
            $this->docblockTypeTransformer
        );
    }
}
