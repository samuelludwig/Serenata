<?php

namespace PhpIntegrator\Parsing\Node\Keyword;

use PhpParser\NodeAbstract;

/**
 * Represents the self keyword.
 */
class Self_ extends NodeAbstract
{
    /**
     * @inheritDoc
     */
    public function getSubNodeNames()
    {
        return [];
    }
}
