<?php

namespace PhpIntegrator\Parsing\Node\Keyword;

use PhpParser\Node\Expr;

/**
 * Represents the parent keyword.
 */
final class Parent_ extends Expr
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
        return 'Expr_Parent';
    }
}
