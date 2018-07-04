<?php

namespace Serenata\Autocompletion;

/**
 * Retrieves tokens that should act as boundary tokens for autocompletion prefixes (the part of the word that is being
 * typed).
 */
final class DefaultAutocompletionPrefixBoundaryTokenRetriever implements
    AutocompletionPrefixBoundaryTokenRetrieverInterface
{
    /**
     * @inheritDoc
     */
    public function retrieve(): array
    {
        return [
            " ",
            "\n",
            "\t",
            "(",
            ")",
            "{",
            "}",
            "[",
            "]",
            "+",
            "-",
            "*",
            "/",
            "^",
            "|",
            "&",
            ":",
            "!",
            "?",
            "@",
            "#",
            "%",
            ">",
            "<",
            "=",
            ",",
            ".",
            ";",
            '~'
        ];
    }
}
