<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\GlobalFunctionExistenceChecker;

/**
 * Factory that produces instances of {@see UnknownGlobalFunctionAnalyzer}.
 */
class UnknownGlobalFunctionAnalyzerFactory
{
    /**
     * @var GlobalFunctionExistenceChecker
     */
    protected $globalFunctionExistenceChecker;

    /**
     * @param GlobalFunctionExistenceChecker $globalFunctionExistenceChecker
     */
    public function __construct(GlobalFunctionExistenceChecker $globalFunctionExistenceChecker)
    {
        $this->globalFunctionExistenceChecker = $globalFunctionExistenceChecker;
    }

    /**
     * @return UnknownGlobalFunctionAnalyzer
     */
    public function create(): UnknownGlobalFunctionAnalyzer
    {
        return new UnknownGlobalFunctionAnalyzer(
            $this->globalFunctionExistenceChecker
        );
    }
}
