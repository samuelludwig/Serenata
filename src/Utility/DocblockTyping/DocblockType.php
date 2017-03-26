<?php

namespace PhpIntegrator\Utility\DocblockTyping;

use PhpIntegrator\Utility\SpecialDocblockType;

/**
 * Represents a docblock type.
 *
 * This is a value object and immutable.
 */
class DocblockType
{
    /**
     * @var string
     */
    protected const ARRAY_TYPE_HINT_REGEX = '/^(.+)\[\]$/';

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $type
     */
    protected function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param string $type
     *
     * @return static
     */
    public static function createFromString(string $type)
    {
        if ($type === SpecialDocblockType::ARRAY_ || (preg_match(self::ARRAY_TYPE_HINT_REGEX, $type) === 1)) {
            return new ArrayDocblockType($type);
        }

        return new static($type);
    }
}
