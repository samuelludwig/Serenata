<?php

namespace Serenata\Autocompletion;

/**
 * Escapes text for use in snippet insertion texts in autocompletion suggestions in accordance with the LSP.
 *
 * @see https://microsoft.github.io/language-server-protocol/specifications/specification-current/#grammar
 */
final class SnippetInsertionTextEscaper
{
    /**
     * @param string $text
     *
     * @return string
     */
    public static function escape(string $text): string
    {
        $escapedText = $text;
        $escapedText = str_replace('\\', '\\\\', $escapedText);
        $escapedText = str_replace('$', '\$', $escapedText);
        $escapedText = str_replace('}', '\}', $escapedText);

        return $escapedText;
    }
}
