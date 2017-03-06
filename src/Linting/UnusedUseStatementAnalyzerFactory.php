<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Parsing\DocblockParser;

/**
 * Factory that produces instances of {@see UnusedUseStatementAnalyzer}.
 */
class UnusedUseStatementAnalyzerFactory
{
    /**
     * @var TypeAnalyzer
     */
    protected $typeAnalyzer;

    /**
     * @var DocblockParser
     */
    protected $docblockParser;

    /**
     * @param TypeAnalyzer   $typeAnalyzer
     * @param DocblockParser $docblockParser
     */
    public function __construct(TypeAnalyzer $typeAnalyzer, DocblockParser $docblockParser)
    {
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockParser = $docblockParser;
    }

    /**
     * @return UnusedUseStatementAnalyzer
     */
    public function create(): UnusedUseStatementAnalyzer
    {
        return new UnusedUseStatementAnalyzer(
            $this->typeAnalyzer,
            $this->docblockParser
        );
    }
}
