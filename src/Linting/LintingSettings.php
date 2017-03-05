<?php

namespace PhpIntegrator\Linting;

/**
 * Describes settings to pass to a lint operation.
 *
 * This is a value object and immutable.
 */
class LintingSettings
{
    /**
     * @var bool
     */
    protected $lintUnknownClasses;

    /**
     * @var bool
     */
    protected $lintUnknownMembers;

    /**
     * @var bool
     */
    protected $lintUnknownGlobalFunctions;

    /**
     * @var bool
     */
    protected $lintUnknownGlobalConstants;

    /**
     * @var bool
     */
    protected $lintDocblockCorrectness;

    /**
     * @var bool
     */
    protected $lintUnusedUseStatements;

    /**
     * @param bool $lintUnknownClasses
     * @param bool $lintUnknownMembers
     * @param bool $lintUnknownGlobalFunctions
     * @param bool $lintUnknownGlobalConstants
     * @param bool $lintDocblockCorrectness
     * @param bool $lintUnusedUseStatements
     */
    public function __construct(
        bool $lintUnknownClasses,
        bool $lintUnknownMembers,
        bool $lintUnknownGlobalFunctions,
        bool $lintUnknownGlobalConstants,
        bool $lintDocblockCorrectness,
        bool $lintUnusedUseStatements
    ) {
        $this->lintUnknownClasses = $lintUnknownClasses;
        $this->lintUnknownMembers = $lintUnknownMembers;
        $this->lintUnknownGlobalFunctions = $lintUnknownGlobalFunctions;
        $this->lintUnknownGlobalConstants = $lintUnknownGlobalConstants;
        $this->lintDocblockCorrectness = $lintDocblockCorrectness;
        $this->lintUnusedUseStatements = $lintUnusedUseStatements;
    }

    /**
     * @return bool
     */
    public function getLintUnknownClasses(): bool
    {
        return $this->lintUnknownClasses;
    }

    /**
     * @return bool
     */
    public function getLintUnknownMembers(): bool
    {
        return $this->lintUnknownMembers;
    }

    /**
     * @return bool
     */
    public function getLintUnknownGlobalFunctions(): bool
    {
        return $this->lintUnknownGlobalFunctions;
    }

    /**
     * @return bool
     */
    public function getLintUnknownGlobalConstants(): bool
    {
        return $this->lintUnknownGlobalConstants;
    }

    /**
     * @return bool
     */
    public function getLintDocblockCorrectness(): bool
    {
        return $this->lintDocblockCorrectness;
    }

    /**
     * @return bool
     */
    public function getLintUnusedUseStatements(): bool
    {
        return $this->lintUnusedUseStatements;
    }
}
