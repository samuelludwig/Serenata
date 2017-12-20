<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpIntegrator\Analysis\NodeAtOffsetLocatorInterface;

use PhpIntegrator\Indexing\Structures\File;

use PhpParser\Node;

/**
 * Autocompletion provider that first checks if autocompletion suggestions apply at the requested offset and, if so,
 * delegates to another provider.
 */
final class ApplicabilityCheckingAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var AutocompletionProviderInterface
     */
    private $delegate;

    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $nodeAtOffsetLocator;

    /**
     * @var AutocompletionApplicabilityCheckerInterface
     */
    private $autocompletionApplicabilityChecker;

    /**
     * @param AutocompletionProviderInterface             $delegate
     * @param NodeAtOffsetLocatorInterface                $nodeAtOffsetLocator
     * @param AutocompletionApplicabilityCheckerInterface $autocompletionApplicabilityChecker
     */
    public function __construct(
        AutocompletionProviderInterface $delegate,
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        AutocompletionApplicabilityCheckerInterface $autocompletionApplicabilityChecker
    ) {
        $this->delegate = $delegate;
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->autocompletionApplicabilityChecker = $autocompletionApplicabilityChecker;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $node = $this->findNodeAt($code, $offset);

        if ($node !== null && $this->autocompletionApplicabilityChecker->doesApplyTo($node)) {
            return $this->delegate->provide($file, $code, $offset);
        } elseif ($node === null && $this->autocompletionApplicabilityChecker->doesApplyOutsideNodes()) {
            return $this->delegate->provide($file, $code, $offset);
        }

        return [];
    }

    /**
     * @param string $code
     * @param int    $position
     *
     * @return Node|null
     */
    private function findNodeAt(string $code, int $position): ?Node
    {
        $result = $this->nodeAtOffsetLocator->locate($code, $position);

        $node = $result->getNode();
        $nearestInterestingNode = $result->getNearestInterestingNode();

        if (!$node) {
            return null;
        }

        if ($nearestInterestingNode instanceof Node\Expr\FuncCall ||
            $nearestInterestingNode instanceof Node\Expr\ConstFetch ||
            $nearestInterestingNode instanceof Node\Stmt\UseUse
        ) {
            return $nearestInterestingNode;
        }

        return ($node instanceof Node\Name || $node instanceof Node\Identifier) ? $node : $nearestInterestingNode;
    }
}
