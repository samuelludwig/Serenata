<?php

namespace PhpIntegrator\Parsing\Node\Keyword;

use PhpParser\Node\Expr;

/**
 * Represents the static keyword.
 */
final class Static_ extends Expr
{
    /**
     * @inheritDoc
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
