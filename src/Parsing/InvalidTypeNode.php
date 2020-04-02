<?php

namespace Serenata\Parsing;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Type node that represents an invalid type (i.e. syntax errors were present).
 */
final class InvalidTypeNode implements TypeNode
{
    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '';
    }
}
