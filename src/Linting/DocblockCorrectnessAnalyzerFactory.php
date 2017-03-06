<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\DocblockAnalyzer;
use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Parsing\DocblockParser;

/**
 * Factory that produces instances of {@see DocblockCorrectnessAnalyzer}.
 */
class DocblockCorrectnessAnalyzerFactory
{
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
     * @param ClasslikeInfoBuilder $classlikeInfoBuilder
     * @param DocblockParser       $docblockParser
     * @param TypeAnalyzer         $typeAnalyzer
     * @param DocblockAnalyzer     $docblockAnalyzer
     */
    public function __construct(
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        DocblockAnalyzer $docblockAnalyzer
    ) {
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->docblockParser = $docblockParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockAnalyzer = $docblockAnalyzer;
    }

    /**
     * @param string $code
     *
     * @return DocblockCorrectnessAnalyzer
     */
    public function create(string $code): DocblockCorrectnessAnalyzer
    {
        return new DocblockCorrectnessAnalyzer(
            $code,
            $this->classlikeInfoBuilder,
            $this->docblockParser,
            $this->typeAnalyzer,
            $this->docblockAnalyzer
        );
    }
}
