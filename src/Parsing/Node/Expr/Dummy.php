<?php

namespace Serenata\Parsing\Node\Expr;

use PhpParser\Node;

/**
 * Dummy expression that can be inserted in locations were an expression node is expected to be present, but it should
 * not actually contain anything useful.
 */
final class Dummy extends Node\Expr
{
    /**
     * @inheritDoc
     */
    public function getSubNodeNames(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'Expr_Dummy';
    }
}
