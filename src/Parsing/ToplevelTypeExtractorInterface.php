<?php

namespace Serenata\Parsing;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Extracts the top-level types of a type node.
 */
interface ToplevelTypeExtractorInterface
{
    /**
     * Extract the top-level types of a type node.
     *
     * Compound types will be unwrapped to their inner types. Other types will be kept as-is.
     *
     * The intent of this function is to answer the question "what types does this type actually represent". For
     * example, if you have the type of a variable, you may want to know what types you need to autocomplete. If its
     * type is array<Foo>|Bar, you want to know 'array<Foo>' or 'Foo'.
     *
     * @param TypeNode $type
     *
     * @return TypeNode[]
     */
    public function extract(TypeNode $type): array;
}
