<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

use Closure;

/**
 * Represents a compound docblock type.
 *
 * This is a value object and immutable.
 */
class CompoundDocblockType extends DocblockType
{
    /**
     * @var DocblockType[]
     */
    private $parts;

    /**
     * @param DocblockType   $firstPart
     * @param DocblockType[] ...$nextParts
     */
    public function __construct(DocblockType $firstPart, DocblockType ...$nextParts)
    {
        $this->parts = array_merge([$firstPart], $nextParts);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function has(string $type): bool
    {
        return !empty($this->filter(function (DocblockType $part) use ($type) {
            return is_a($part, $type, false);
        }));
    }

    /**
     * @param Closure $predicate
     *
     * @return array
     */
    public function filter(Closure $predicate): array
    {
        return array_filter($this->parts, function (DocblockType $part) use ($predicate) {
            return $predicate($part);
        });
    }

    /**
     * @return DocblockType[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $stringParts = array_map(function (DocblockType $type) {
            return $type->toString();
        }, $this->parts);

        return implode('|', $stringParts);
    }
}
