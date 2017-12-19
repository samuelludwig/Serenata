<?php

namespace PhpIntegrator\Analysis\Autocompletion;

/**
 * Enumeration of suggestion kinds.
 */
final class SuggestionKind
{
    /**
     * @var string
     */
    public const KEYWORD = 'keyword';

    /**
     * @var string
     */
    public const CLASS_ = 'class';

    /**
     * @var string
     */
    public const MIXIN = 'mixin';

    /**
     * @var string
     */
    public const IMPORT = 'import';

    /**
     * @var string
     */
    public const FUNCTION = 'function';

    /**
     * @var string
     */
    public const CONSTANT = 'constant';

    /**
     * @var string
     */
    public const METHOD = 'method';

    /**
     * @var string
     */
    public const PROPERTY = 'property';

    /**
     * @var string
     */
    public const VARIABLE = 'variable';
}
