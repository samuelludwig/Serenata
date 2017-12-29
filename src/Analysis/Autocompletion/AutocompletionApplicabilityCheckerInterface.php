<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpParser\Node;

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
     * @return bool
     */
    public function doesApplyOutsideNodes(): bool;

    /**
     * @param Node $node
     *
     * @return bool
     */
    public function doesApplyTo(Node $node): bool;
}
