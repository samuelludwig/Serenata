<?php

namespace PhpIntegrator\Analysis\Autocompletion;

/**
 * Retrieves tokens that should act as boundary tokens for autocompletion prefixes (the part of the word that is being
 * typed) in docblocks.
 */
final class DocblockAutocompletionPrefixBoundaryTokenRetriever implements
    AutocompletionPrefixBoundaryTokenRetrieverInterface
{
    /**
     * @var AutocompletionPrefixBoundaryTokenRetrieverInterface
     */
    private $defaultAutocompletionPrefixBoundaryTokenRetriever;

    /**
     * @param AutocompletionPrefixBoundaryTokenRetrieverInterface $defaultAutocompletionPrefixBoundaryTokenRetriever
     */
    public function __construct(
        AutocompletionPrefixBoundaryTokenRetrieverInterface $defaultAutocompletionPrefixBoundaryTokenRetriever
    ) {
        $this->defaultAutocompletionPrefixBoundaryTokenRetriever = $defaultAutocompletionPrefixBoundaryTokenRetriever;
    }

    /**
     * @inheritDoc
     */
    public function retrieve(): array
    {
        $tokens = $this->defaultAutocompletionPrefixBoundaryTokenRetriever->retrieve();

        $offset = array_search('@', $tokens, true);

        unset($tokens[$offset]);

        return array_values($tokens);
    }
}
