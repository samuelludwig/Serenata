<?php

namespace Serenata\Symbols;

/**
 * Enumeration of symbol kinds.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#textDocument_documentSymbol
 */
final class SymbolKind
{
    /**
     * @var int
     */
    public const FILE = 1;

    /**
     * @var int
     */
    public const MODULE = 2;

    /**
     * @var int
     */
    public const NAMESPACE = 3;

    /**
     * @var int
     */
    public const PACKAGE = 4;

    /**
     * @var int
     */
    public const CLASS_ = 5;

    /**
     * @var int
     */
    public const METHOD = 6;

    /**
     * @var int
     */
    public const PROPERTY = 7;

    /**
     * @var int
     */
    public const FIELD = 8;

    /**
     * @var int
     */
    public const CONSTRUCTOR = 9;

    /**
     * @var int
     */
    public const ENUM = 10;

    /**
     * @var int
     */
    public const INTERFACE_ = 11;

    /**
     * @var int
     */
    public const FUNCTION_ = 12;

    /**
     * @var int
     */
    public const VARIABLE = 13;

    /**
     * @var int
     */
    public const CONSTANT = 14;

    /**
     * @var int
     */
    public const STRING_ = 15;

    /**
     * @var int
     */
    public const NUMBER = 16;

    /**
     * @var int
     */
    public const BOOLEAN_ = 17;

    /**
     * @var int
     */
    public const ARRAY_ = 18;

    /**
     * @var int
     */
    public const OBJECT_ = 19;

    /**
     * @var int
     */
    public const KEY = 20;

    /**
     * @var int
     */
    public const NULL = 21;

    /**
     * @var int
     */
    public const ENUMMEMBER = 22;

    /**
     * @var int
     */
    public const STRUCT = 23;

    /**
     * @var int
     */
    public const EVENT = 24;

    /**
     * @var int
     */
    public const OPERATOR = 25;

    /**
     * @var int
     */
    public const TYPEPARAMETER = 26;
}
