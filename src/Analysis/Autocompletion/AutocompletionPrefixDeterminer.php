<?php

namespace PhpIntegrator\Analysis\Autocompletion;

/**
 * Determines the prefix (the part of the word that is being typed) for autocompletion purposes at a specific location.
 */
final class AutocompletionPrefixDeterminer implements AutocompletionPrefixDeterminerInterface
{
    /**
     * @var string[]
     */
    private const BOUNDARY_TOKEN_MAP = [
        " "  => true,
        "\n" => true,
        "\t" => true,
        "("  => true,
        ")"  => true,
        "{"  => true,
        "}"  => true,
        "["  => true,
        "]"  => true,
        "+"  => true,
        "-"  => true,
        "*"  => true,
        "/"  => true,
        "^"  => true,
        "|"  => true,
        "&"  => true,
        ":"  => true,
        "!"  => true,
        "?"  => true,
        "@"  => true,
        "#"  => true,
        "%"  => true,
        ">"  => true,
        "<"  => true,
        "="  => true,
        "\\" => true
    ];

    /**
     * @inheritDoc
     */
    public function determine(string $code, int $offset): string
    {
        $i = max($offset - 1, 0);

        while ($i > 0) {
            if (isset(self::BOUNDARY_TOKEN_MAP[$code[$i]])) {
                ++$i; // Don't include the boundary character itself.
                break;
            }

            --$i;
        }

        return substr($code, $i, $offset - $i);
    }
}
