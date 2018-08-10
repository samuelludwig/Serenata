<?php

namespace Serenata\Autocompletion;

use Serenata\Common\Position;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Evaluates if parantheses are necessary in autocompletion suggestions for functions.
 */
final class FunctionAutocompletionSuggestionParanthesesNecessityEvaluator
{
    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return bool
     */
    public function evaluate(TextDocumentItem $textDocumentItem, Position $position): bool
    {
        $code = $textDocumentItem->getText();
        $offset = $position->getAsByteOffsetInString($textDocumentItem->getText(), PositionEncoding::VALUE);

        $length = strlen($code);

        for ($i = $offset; $i < $length; ++$i) {
            if ($code[$i] === '(') {
                return false;
            } elseif ($this->isWhitespace($code[$i])) {
                continue;
            }

            return true;
        }

        return true;
    }

    /**
     * @param string $character
     *
     * @return bool
     */
    private function isWhitespace(string $character): bool
    {
        return ($character === ' ' || $character === "\r" || $character === "\n" || $character === "\t");
    }
}
