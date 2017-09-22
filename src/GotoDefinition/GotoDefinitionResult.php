<?php

namespace PhpIntegrator\GotoDefinition;

/**
 * The result of a goto definition request.
 */
class GotoDefinitionResult
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var int
     */
    private $line;

    /**
     * @param string $uri
     * @param int    $line
     */
    public function __construct(string $uri, int $line)
    {
        $this->uri = $uri;
        $this->line = $line;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }
}
