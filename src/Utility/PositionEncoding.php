<?php

namespace Serenata\Utility;

/**
 * Contains the encoding used for position character offsets.
 *
 * Version 3 of the specification says this has to be UTF-16 (see the link below), but the previous versions don't nor
 * do some clients (e.g. Atom's language server client) appear to do any conversion; they just supply character offsets
 * in the local encoding of the opened file.
 *
 * Standardizing upon an encoding for character offsets seems reasonable enough, though I don't understand why this has
 * to be UTF-16 and not just UTF-8, which is by far the most commonly used encoding. We can always change this at a
 * later date, though.
 *
 * For these reasons, we just maintain UTF-8 for the moment as we convert everything to UTF-8 already.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#text-documents
 */
final class PositionEncoding
{
    /**
     * @var string
     */
    public const VALUE = 'UTF-8';
}
