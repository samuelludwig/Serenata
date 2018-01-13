<?php

namespace PhpIntegrator\Autocompletion\ApplicabilityChecking;

use PhpParser\Comment;

use PhpIntegrator\Analysis\NodeAtOffsetLocatorResult;

/**
 * Checks if docblock tag autocompletion applies for a specific node.
 */
final class DocblockTagAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
{
    /**
     * @inheritDoc
     */
    public function doesApplyToPrefix(string $prefix): bool
    {
        // Prevent trigger happy suggestions when user hasn't even actually typed anything, resulting in some editors
        // immediately and unwantedly confirming a suggestion when the user attempted to create a newline.
        return $prefix !== '' && $prefix[0] === '@';
    }

    /**
     * @inheritDoc
     */
    public function doesApplyTo(NodeAtOffsetLocatorResult $nodeAtOffsetLocatorResult): bool
    {
        return $nodeAtOffsetLocatorResult->getComment() instanceof Comment\Doc;
    }
}
