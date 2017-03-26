<?php

namespace PhpIntegrator\Utility\DocblockTyping;

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
        if ($type === SpecialDocblockTypeString::ARRAY_ || (preg_match(self::ARRAY_TYPE_HINT_REGEX, $type) === 1)) {
            return new ArrayDocblockType($type);
        }

        $specialTypes = [
            SpecialDocblockTypeString::STRING_,
            SpecialDocblockTypeString::INT_,
            SpecialDocblockTypeString::BOOL_,
            SpecialDocblockTypeString::FLOAT_,
            SpecialDocblockTypeString::OBJECT_,
            SpecialDocblockTypeString::MIXED_,
            SpecialDocblockTypeString::ARRAY_,
            SpecialDocblockTypeString::RESOURCE_,
            SpecialDocblockTypeString::VOID_,
            SpecialDocblockTypeString::NULL_,
            SpecialDocblockTypeString::CALLABLE_,
            SpecialDocblockTypeString::FALSE_,
            SpecialDocblockTypeString::TRUE_,
            SpecialDocblockTypeString::SELF_,
            SpecialDocblockTypeString::STATIC_,
            SpecialDocblockTypeString::PARENT_,
            SpecialDocblockTypeString::THIS_,
            SpecialDocblockTypeString::ITERABLE_
        ];

        return in_array($type, $specialTypes, true) ? new SpecialDocblockType($type) : new ClassDocblockType($type);
    }
}
