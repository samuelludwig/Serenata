<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\ClasslikeExistenceChecker;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

use PhpIntegrator\Parsing\DocblockParser;

/**
 * Factory that produces instances of {@see UnknownClassAnalyzer}.
 */
class UnknownClassAnalyzerFactory
{
    /**
     * @var ClasslikeExistenceChecker
     */
    protected $classlikeExistenceChecker;

    /**
     * @var FileTypeResolverFactoryInterface
     */
    protected $fileTypeResolverFactory;

    /**
     * @var TypeAnalyzer
     */
    protected $typeAnalyzer;

    /**
     * @var DocblockParser
     */
    protected $docblockParser;

    /**
     * @param ClasslikeExistenceChecker        $classlikeExistenceChecker
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param DocblockParser                   $docblockParser
     */
    public function __construct(
        ClasslikeExistenceChecker $classlikeExistenceChecker,
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        TypeAnalyzer $typeAnalyzer,
        DocblockParser $docblockParser
    ) {
        $this->classlikeExistenceChecker = $classlikeExistenceChecker;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockParser = $docblockParser;
    }

    /**
     * @param string $file
     *
     * @return UnknownClassAnalyzer
     */
    public function create(string $file): UnknownClassAnalyzer
    {
        return new UnknownClassAnalyzer(
            $this->classlikeExistenceChecker,
            $this->fileTypeResolverFactory->create($file),
            $this->typeAnalyzer,
            $this->docblockParser
        );
    }
}
