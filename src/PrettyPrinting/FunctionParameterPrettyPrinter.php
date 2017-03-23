<?php

namespace PhpIntegrator\PrettyPrinting;

/**
 * Pretty prints function and method parameters.
 */
class FunctionParameterPrettyPrinter
{
    /**
     * @param array $parameter
     *
     * @return string
     */
    public function print(array $parameter): string
    {
        $label = '';

        if (!empty($parameter['types'])) {
            $label .= implode('|', array_map(function (array $type) {
                return $type['type'];
            }, $parameter['types']));

            $label .= ' ';
        }

        if ($parameter['isVariadic']) {
            $label .= '...';
        }

        if ($parameter['isReference']) {
            $label .= '&';
        }

        $label .= '$' . $parameter['name'];

        if ($parameter['defaultValue'] !== null) {
            $label .= ' = ' . $parameter['defaultValue'];
        }

        return $label;
    }
}
