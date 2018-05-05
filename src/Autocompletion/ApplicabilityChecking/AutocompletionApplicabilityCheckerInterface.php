<?php

namespace Serenata\Autocompletion\ApplicabilityChecking;

use Serenata\Analysis\NodeAtOffsetLocatorResult;

/**
 * Checks if autocompletion applies to a specific node.
 */
interface AutocompletionApplicabilityCheckerInterface
{
    /**
     * @param string $prefix
     *
     * @return bool
     */
    public function doesApplyToPrefix(string $prefix): bool;

    /**
     * @param NodeAtOffsetLocatorResult $node
     *
     * @return bool
     */
    public function doesApplyTo(NodeAtOffsetLocatorResult $node): bool;
}
