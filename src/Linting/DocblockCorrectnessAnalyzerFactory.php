<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\DocblockAnalyzer;
use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\ParameterDocblockTypeSemanticEqualityChecker;

use PhpIntegrator\Parsing\DocblockParser;

/**
 * Factory that produces instances of {@see DocblockCorrectnessAnalyzer}.
 */
class DocblockCorrectnessAnalyzerFactory
{
    /**
     * @var ParameterDocblockTypeSemanticEqualityChecker
     */
    private $parameterDocblockTypeSemanticEqualityChecker;

    /**
     * @var ClasslikeInfoBuilder
     */
    private $classlikeInfoBuilder;

    /**
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var DocblockAnalyzer
     */
    private $docblockAnalyzer;

    /**
     * @param ParameterDocblockTypeSemanticEqualityChecker $parameterDocblockTypeSemanticEqualityChecker
     * @param ClasslikeInfoBuilder                         $classlikeInfoBuilder
     * @param DocblockParser                               $docblockParser
     * @param TypeAnalyzer                                 $typeAnalyzer
     * @param DocblockAnalyzer                             $docblockAnalyzer
     */
    public function __construct(
        ParameterDocblockTypeSemanticEqualityChecker $parameterDocblockTypeSemanticEqualityChecker,
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        DocblockAnalyzer $docblockAnalyzer
    ) {
        $this->parameterDocblockTypeSemanticEqualityChecker = $parameterDocblockTypeSemanticEqualityChecker;
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
            $file,
            $code,
            $this->parameterDocblockTypeSemanticEqualityChecker,
            $this->classlikeInfoBuilder,
            $this->docblockParser,
            $this->typeAnalyzer,
            $this->docblockAnalyzer
        );
    }
}
