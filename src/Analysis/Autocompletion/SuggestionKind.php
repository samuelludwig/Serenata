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
}
