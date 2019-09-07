<?php

namespace Serenata\Parsing\Node\Keyword;

use PhpParser\Node\Expr;

/**
 * phpcs:disable
 *
 * Represents the parent keyword.
 */
final class Parent_ extends Expr
{
    // phpcs:enable
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
