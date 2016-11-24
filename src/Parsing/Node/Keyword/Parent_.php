<?php

namespace PhpIntegrator\Parsing\Node\Keyword;

use PhpParser\NodeAbstract;

/**
 * Represents the parent keyword.
 */
class Parent_ extends NodeAbstract
{
    /**
     * @inheritDoc
     */
    public function getSubNodeNames()
    {
        return [];
    }
}
