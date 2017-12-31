<?php

namespace PhpIntegrator\Analysis\Autocompletion;

/**
 * Evaluates if parantheses are necessary in autocompletion suggestions for functions.
 */
final class FunctionAutocompletionSuggestionParanthesesNecessityEvaluator
{
    /**
     * @param string $code
     * @param int    $offset
     *
     * @return bool
     */
    public function evaluate(string $code, int $offset): bool
    {
        $length = mb_strlen($code);

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
