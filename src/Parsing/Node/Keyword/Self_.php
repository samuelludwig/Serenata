<?php

namespace Serenata\Parsing\Node\Keyword;

use PhpParser\Node\Expr;

/**
 * Represents the self keyword.
 */
final class Self_ extends Expr
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
        return 'Expr_Self';
    }
}
