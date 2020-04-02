<?php

namespace Serenata\Parsing;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Parses docblock type specifications into more usable objects.
 *
 * Note that this is tied to PHPStan's type nodes and AST as output.
 *
 * @see https://phpdoc.org/docs/latest/references/phpdoc/types.html
 */
interface DocblockTypeParserInterface
{
    /**
     * @param string $specification
     *
     * @return TypeNode
     */
    public function parse(string $specification): TypeNode;
}
