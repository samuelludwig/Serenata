<?php

namespace Serenata\Utility;

use Closure;
use Traversable;
use ArrayIterator;
use IteratorAggregate;

/**
 * Represents an immutable set of values.
 *
 * @final
 *
 * @template T
 *
 * @implements IteratorAggregate<int,T>
 */
class ImmutableSet implements IteratorAggregate
{
    /**
     * @var T[]
     */
    private $elements;

    /**
     * @param T ...$elements
     */
    public function __construct(...$elements)
    {
        $this->elements = $elements;
    }

    /**
     * @return T[]
     */
    public function toArray(): array
    {
        return $this->elements;
    }

    /**
     * @param T $element
     *
     * @return bool
     */
    public function has($element): bool
    {
        // The strict ruleset enforces the third parameter to be true, but we need it to be loose in some cases, so this
        // works around it.
        $silencePhpStan = 'in_array';

        return $silencePhpStan($element, $this->elements, $this->isStrict());
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->toArray()) === 0;
    }

    /**
     * @param ImmutableSet<T> $other
     *
     * @return bool
     */
    public function equals(ImmutableSet $other): bool
    {
        return
            count(array_diff($this->toArray(), $other->toArray())) === 0 &&
            count(array_diff($other->toArray(), $this->toArray())) === 0;
    }

    /**
     * @param Closure $closure
     *
     * @return static<T>
     */
    public function filter(Closure $closure): ImmutableSet
    {
        return new static(...array_filter($this->toArray(), $closure));
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * @return bool
     *
     * @api
     */
    protected function isStrict(): bool
    {
        return true;
    }
}
