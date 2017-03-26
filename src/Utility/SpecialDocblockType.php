<?php

namespace PhpIntegrator\Utility;

/**
 * Enumeration of special docblock types.
 */
class SpecialDocblockType
{
    /**
     * @var string
     */
    const STRING_   = 'string';

    /**
     * @var string
     */
    const INT_      = 'int';

    /**
     * @var string
     */
    const BOOL_     = 'bool';

    /**
     * @var string
     */
    const FLOAT_    = 'float';

    /**
     * @var string
     */
    const OBJECT_   = 'object';

    /**
     * @var string
     */
    const MIXED_    = 'mixed';

    /**
     * @var string
     */
    const ARRAY_    = 'array';

    /**
     * @var string
     */
    const RESOURCE_ = 'resource';

    /**
     * @var string
     */
    const VOID_     = 'void';

    /**
     * @var string
     */
    const NULL_     = 'null';

    /**
     * @var string
     */
    const CALLABLE_ = 'callable';

    /**
     * @var string
     */
    const FALSE_    = 'false';

    /**
     * @var string
     */
    const TRUE_     = 'true';

    /**
     * @var string
     */
    const SELF_     = 'self';

    /**
     * @var string
     */
    const STATIC_   = 'static';

    /**
     * @var string
     */
    const PARENT_   = 'parent';

    /**
     * @var string
     */
    const THIS_     = '$this';

    /**
     * @var string
     */
    const ITERABLE_ = 'iterable';
}
