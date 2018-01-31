<?php

namespace PhpIntegrator\Autocompletion;

/**
 * Determines the prefix (the part of the word that is being typed) for autocompletion purposes at a specific location.
 */
final class AutocompletionPrefixDeterminer implements AutocompletionPrefixDeterminerInterface
{
    /**
     * @var AutocompletionPrefixBoundaryTokenRetrieverInterface
     */
    private $autocompletionPrefixBoundaryTokenRetriever;

    /**
     * @param AutocompletionPrefixBoundaryTokenRetrieverInterface $autocompletionPrefixBoundaryTokenRetriever
     */
    public function __construct(
        AutocompletionPrefixBoundaryTokenRetrieverInterface $autocompletionPrefixBoundaryTokenRetriever
    ) {
        $this->autocompletionPrefixBoundaryTokenRetriever = $autocompletionPrefixBoundaryTokenRetriever;
    }

    /**
     * @inheritDoc
     */
    public function determine(string $code, int $offset): string
    {
        $i = max($offset - 1, 0);

        $tokens = $this->autocompletionPrefixBoundaryTokenRetriever->retrieve();

        while ($i > 0) {
            if (in_array($code[$i], $tokens, true)) {
                ++$i; // Don't include the boundary character itself.
                break;
            }

            --$i;
        }

        return substr($code, $i, $offset - $i);
    }
}
