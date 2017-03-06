<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\DocblockAnalyzer;
use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

use PhpIntegrator\Parsing\DocblockParser;

/**
 * Factory that produces instances of {@see DocblockCorrectnessAnalyzer}.
 */
class DocblockCorrectnessAnalyzerFactory
{
    /**
     * @var FileTypeResolverFactoryInterface
     */
    protected $fileTypeResolverFactory;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @var DocblockParser
     */
    protected $docblockParser;

    /**
     * @var TypeAnalyzer
     */
    protected $typeAnalyzer;

    /**
     * @var DocblockAnalyzer
     */
    protected $docblockAnalyzer;

    /**
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param ClasslikeInfoBuilder             $classlikeInfoBuilder
     * @param DocblockParser                   $docblockParser
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param DocblockAnalyzer                 $docblockAnalyzer
     */
    public function __construct(
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        DocblockAnalyzer $docblockAnalyzer
    ) {
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->docblockParser = $docblockParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockAnalyzer = $docblockAnalyzer;
    }

    /**
     * @param string $file
     * @param string $code
     *
     * @return DocblockCorrectnessAnalyzer
     */
    public function create(string $file, string $code): DocblockCorrectnessAnalyzer
    {
        return new DocblockCorrectnessAnalyzer(
            $code,
            $this->fileTypeResolverFactory->create($file),
            $this->classlikeInfoBuilder,
            $this->docblockParser,
            $this->typeAnalyzer,
            $this->docblockAnalyzer
        );
    }
}
