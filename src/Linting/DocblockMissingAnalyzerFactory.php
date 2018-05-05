<?php

namespace Serenata\Linting;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\TypeAnalyzer;

/**
 * Factory that produces instances of {@see DocblockMissingAnalyzer}.
 */
class DocblockMissingAnalyzerFactory
{
    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @param TypeAnalyzer                  $typeAnalyzer
     * @param ClasslikeInfoBuilderInterface $classlikeInfoBuilder
     */
    public function __construct(TypeAnalyzer $typeAnalyzer, ClasslikeInfoBuilderInterface $classlikeInfoBuilder)
    {
        $this->typeAnalyzer = $typeAnalyzer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param string $code
     *
     * @return DocblockMissingAnalyzer
     */
    public function create(string $code): DocblockMissingAnalyzer
    {
        return new DocblockMissingAnalyzer(
            $code,
            $this->typeAnalyzer,
            $this->classlikeInfoBuilder
        );
    }
}
