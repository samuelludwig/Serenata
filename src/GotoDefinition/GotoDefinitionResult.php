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
    private $offset;

    /**
     * @param string $uri
     * @param int    $offset
     */
    public function __construct(string $uri, int $offset)
    {
        $this->uri = $uri;
        $this->offset = $offset;
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
    public function getOffset(): int
    {
        return $this->offset;
    }
}
