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
        $node = $this->nodeAtOffsetLocator->locate($code, $offset)->getNode();

        if ($node !== null && $this->autocompletionApplicabilityChecker->doesApplyTo($node)) {
            return $this->delegate->provide($file, $code, $offset);
        } elseif ($node === null && $this->autocompletionApplicabilityChecker->doesApplyOutsideNodes()) {
            return $this->delegate->provide($file, $code, $offset);
        }

        return [];
    }
}
