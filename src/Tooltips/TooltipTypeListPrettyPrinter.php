<?php

namespace Serenata\Tooltips;

use Serenata\PrettyPrinting\TypeListPrettyPrinter;

/**
 * Pretty prints type lists for use in tooltips.
 */
final class TooltipTypeListPrettyPrinter
{
    /**
     * @var TypeListPrettyPrinter
     */
    private $typeListPrettyPrinter;

    /**
     * @param TypeListPrettyPrinter $typeListPrettyPrinter
     */
    public function __construct(TypeListPrettyPrinter $typeListPrettyPrinter)
    {
        $this->typeListPrettyPrinter = $typeListPrettyPrinter;
    }

    /**
     * @param string[] $types
     *
     * @return string
     */
    public function print(array $types): string
    {
        if (count($types) === 0) {
            return '(Not known)';
        }

        $value = $this->typeListPrettyPrinter->print($types);

        return str_replace('|', '&#124;', $value);
    }
}
