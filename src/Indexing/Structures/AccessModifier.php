<?php

namespace Serenata\Indexing\Structures;

/**
 * Represents an access modifier.
 */
class AccessModifier
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->id = uniqid('', true);
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
