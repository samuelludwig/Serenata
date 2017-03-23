<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\GlobalConstantExistenceChecker;

/**
 * Factory that produces instances of {@see UnknownGlobalConstantAnalyzer}.
 */
class UnknownGlobalConstantAnalyzerFactory
{
    /**
     * @var GlobalConstantExistenceChecker
     */
    private $globalConstantExistenceChecker;

    /**
     * @param GlobalConstantExistenceChecker $globalConstantExistenceChecker
     */
    public function __construct(GlobalConstantExistenceChecker $globalConstantExistenceChecker)
    {
        $this->globalConstantExistenceChecker = $globalConstantExistenceChecker;
    }

    /**
     * @return UnknownGlobalConstantAnalyzer
     */
    public function create(): UnknownGlobalConstantAnalyzer
    {
        return new UnknownGlobalConstantAnalyzer(
            $this->globalConstantExistenceChecker
        );
    }
}
