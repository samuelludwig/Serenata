<?php

namespace Serenata\Autocompletion;

use Serenata\Common\Position;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

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
    public function determine(string $source, Position $position): string
    {
        $offset = $position->getAsByteOffsetInString($source, PositionEncoding::VALUE);

        $i = max($offset - 1, 0);

        $tokens = $this->autocompletionPrefixBoundaryTokenRetriever->retrieve();

        while ($i > 0) {
            if (in_array($source[$i], $tokens, true)) {
                ++$i; // Don't include the boundary character itself.
                break;
            }

            --$i;
        }

        return substr($source, $i, $offset - $i);
    }
}
