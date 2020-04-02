<?php

namespace Serenata\Parsing;

/**
 * Enumeration of string values of special docblock types.
 */
class SpecialDocblockTypeIdentifierLiteral
{
    /**
     * @var string
     */
    public const STRING_ = 'string';

    /**
     * @var string
     */
    public const INT_ = 'int';

    /**
     * @var string
     */
    public const INTEGER_ = 'integer';

    /**
     * @var string
     */
    public const BOOL_ = 'bool';

    /**
     * @var string
     */
    public const BOOLEAN_ = 'boolean';

    /**
     * @var string
     */
    public const FLOAT_ = 'float';

    /**
     * @var string
     */
    public const DOUBLE_ = 'double';

    /**
     * @var string
     */
    public const OBJECT_ = 'object';

    /**
     * @var string
     */
    public const MIXED_ = 'mixed';

    /**
     * @var string
     */
    public const ARRAY_ = 'array';

    /**
     * @var string
     */
    public const RESOURCE_ = 'resource';

    /**
     * @var string
     */
    public const VOID_ = 'void';

    /**
     * @var string
     */
    public const NULL_ = 'null';

    /**
     * @var string
     */
    public const CALLABLE_ = 'callable';

    /**
     * @var string
     */
    public const FALSE_ = 'false';

    /**
     * @var string
     */
    public const TRUE_ = 'true';

    /**
     * @var string
     */
    public const SELF_ = 'self';

    /**
     * @var string
     */
    public const STATIC_ = 'static';

    /**
     * @var string
     */
    public const THIS_ = '$this';

    /**
     * @var string
     */
    public const ITERABLE_ = 'iterable';

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return [
            self::STRING_,
            self::INT_,
            self::INTEGER_,
            self::BOOL_,
            self::BOOLEAN_,
            self::FLOAT_,
            self::DOUBLE_,
            self::OBJECT_,
            self::MIXED_,
            self::ARRAY_,
            self::RESOURCE_,
            self::VOID_,
            self::NULL_,
            self::CALLABLE_,
            self::FALSE_,
            self::TRUE_,
            self::SELF_,
            self::STATIC_,
            self::THIS_,
            self::ITERABLE_,
        ];
    }
}
