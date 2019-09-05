<?php

namespace Serenata\GotoDefinition;

use JsonSerializable;

use Serenata\Utility\Location;

/**
 * The result of a goto definition request.
 */
final class GotoDefinitionResponse implements JsonSerializable
{
    /**
     * @var Location|Location[]|null
     */
    private $result;

    /**
     * @param Location|Location[]|null $result
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    /**
     * @return Location|Location[]|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'result' => $this->getResult(),
        ];
    }
}
