<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpParser\Node;

use PhpIntegrator\Analysis\NodeAtOffsetLocatorResult;

/**
 * Checks if parameter name autocompletion applies for a specific node.
 */
final class ParameterNameAutocompletionApplicabilityChecker implements AutocompletionApplicabilityCheckerInterface
{
    /**
     * @inheritDoc
     */
    public function doesApplyToPrefix(string $prefix): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function doesApplyTo(NodeAtOffsetLocatorResult $nodeAtOffsetLocatorResult): bool
    {
        if ($nodeAtOffsetLocatorResult->getNode() === null) {
            return false;
        }

        return $this->doesApplyToNode($nodeAtOffsetLocatorResult->getNode());
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    private function doesApplyToNode(Node $node): bool
    {
        if ($node instanceof Node\Expr\Variable) {
            $parent = $node->getAttribute('parent', false);

            return $parent !== false ? $this->doesApplyToNode($parent) : false;
        }

        return $node instanceof Node\Param;
    }
}
