<?php

namespace PhpIntegrator\Autocompletion;

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
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @param AutocompletionProviderInterface             $delegate
     * @param NodeAtOffsetLocatorInterface                $nodeAtOffsetLocator
     * @param AutocompletionApplicabilityCheckerInterface $autocompletionApplicabilityChecker
     * @param AutocompletionPrefixDeterminerInterface     $autocompletionPrefixDeterminer
     */
    public function __construct(
        AutocompletionProviderInterface $delegate,
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        AutocompletionApplicabilityCheckerInterface $autocompletionApplicabilityChecker,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
    ) {
        $this->delegate = $delegate;
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->autocompletionApplicabilityChecker = $autocompletionApplicabilityChecker;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        if (!$this->autocompletionApplicabilityChecker->doesApplyToPrefix($prefix)) {
            return [];
        }

        // The position the position is at may already be the start of another node. We're interested in what's just
        // before the position (usually the cursor), not what is "at" or "just to the right" of the cursor, hence the
        // -1.
        $nodeResult = $this->nodeAtOffsetLocator->locate($code, $offset - 1);

        return $this->autocompletionApplicabilityChecker->doesApplyTo($nodeResult) ?
            $this->delegate->provide($file, $code, $offset) :
            [];
    }
}
