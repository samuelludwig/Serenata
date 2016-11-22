<?php

namespace PhpIntegrator\Parsing\Node\Keyword;

use PhpParser\NodeAbstract;

/**
 * Represents the static keyword.
 */
class Static_ extends NodeAbstract
{
    /**
     * @inheritDoc
     */
    public function getSubNodeNames()
    {
        return [];
    }
}
