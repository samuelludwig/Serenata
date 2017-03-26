<?php

namespace PhpIntegrator\Utility;

use ArrayIterator;
use IteratorAggregate;

/**
 * Represents an immutable set of values.
 */
class ImmutableSet implements IteratorAggregate
{
    /**
     * @var mixed[]
     */
    private $elements;

    /**
     * @param mixed[] ...$elements
     */
    public function __construct(...$elements)
    {
        $this->elements = $elements;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return $this->elements;
    }

    /**
     * @param mixed $element
     *
     * @return bool
     */
    public function has($element): bool
    {
        return in_array($element, $this->elements, $this->isStrict());
    }

    /**
     * @param ImmutableSet $other
     *
     * @return bool
     */
    public function equals(ImmutableSet $other): bool
    {
        return
            empty(array_diff($this->toArray(), $other->toArray())) &&
            empty(array_diff($other->toArray(), $this->toArray()));
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
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
