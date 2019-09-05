<?php

namespace Serenata\Analysis\Visiting;

/**
 * Kinds of use statements.
 */
final class UseStatementKind
{
    /**
     * @var string
     */
    public const TYPE_CLASSLIKE = 'classlike';

    /**
     * @var string
     */
    public const TYPE_FUNCTION = 'function';

    /**
     * @var string
     */
    public const TYPE_CONSTANT = 'constant';
}
