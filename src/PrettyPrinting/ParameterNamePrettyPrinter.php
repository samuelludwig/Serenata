<?php

namespace Serenata\PrettyPrinting;

/**
 * Pretty prints parameter names.
 */
final class ParameterNamePrettyPrinter
{
    /**
     * @param array<string,mixed> $parameter
     *
     * @return string
     */
    public function print(array $parameter): string
    {
        $label = '';

        if ($parameter['isVariadic'] === true) {
            $label .= '...';
        }

        if ($parameter['isReference'] === true) {
            $label .= '&';
        }

        return $label . '$' . $parameter['name'];
    }
}
