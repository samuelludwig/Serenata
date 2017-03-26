<?php

namespace PhpIntegrator\Utility\DocblockTyping;

use PhpIntegrator\Utility\ImmutableSet;

use PhpIntegrator\Utility\Typing\Type;
use PhpIntegrator\Utility\Typing\TypeList;

/**
 * Represents a list of docblock types.
 *
 * This is a value object and immutable.
 */
final class DocblockTypeList extends ImmutableSet
{
    /**
     * @var string
     */
    private const TYPE_SPLITTER   = '|';

    /**
     * @param DocblockType[] ...$elements
     */
    public function __construct(DocblockType ...$elements)
    {
        parent::__construct(...$elements);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function hasStringType(string $type): bool
    {
        return $this->has(DocblockType::createFromString($type));
    }

    /**
     * @param string[] ...$types
     *
     * @return self
     */
    public static function createFromStringTypeList(string ...$types): self
    {
        return new self(...array_map(function (string $type) {
            return DocblockType::createFromString($type);
        }, $types));
    }

    /**
     * @param TypeList $typeList
     *
     * @return self
     */
    public static function createFromTypeList(TypeList $typeList): self
    {
        return new self(...array_map(function (Type $type) {
            return DocblockType::createFromString($type->toString());
        }, $typeList->toArray()));
    }

    /**
     * @param string $typeSpecification
     *
     * @return self
     */
    public static function createFromDocblockTypeSpecification(string $typeSpecification): self
    {
        return self::createFromStringTypeList(...explode(self::TYPE_SPLITTER, $typeSpecification));
    }

    /**
     * @inheritDoc
     */
    protected function isStrict(): bool
    {
        return false;
    }
}
