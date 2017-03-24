<?php

namespace PhpIntegrator\PrettyPrinting;

/**
 * Pretty prints function and method parameters.
 */
class FunctionParameterPrettyPrinter
{
    /**
     * @var ParameterDefaultValuePrettyPrinter
     */
    protected $parameterDefaultValuePrettyPrinter;

    /**
     * @var TypeListPrettyPrinter
     */
    protected $typeListPrettyPrinter;

    /**
     * @param ParameterDefaultValuePrettyPrinter $parameterDefaultValuePrettyPrinter
     * @param TypeListPrettyPrinter              $typeListPrettyPrinter
     */
    public function __construct(
        ParameterDefaultValuePrettyPrinter $parameterDefaultValuePrettyPrinter,
        TypeListPrettyPrinter $typeListPrettyPrinter
    ) {
        $this->parameterDefaultValuePrettyPrinter = $parameterDefaultValuePrettyPrinter;
        $this->typeListPrettyPrinter = $typeListPrettyPrinter;
    }

    /**
     * @param array $parameter
     *
     * @return string
     */
    public function print(array $parameter): string
    {
        $label = '';

        if (!empty($parameter['types'])) {
            $label .= $this->typeListPrettyPrinter->print(array_map(function (array $type) {
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
            $label .= ' = ' . $this->parameterDefaultValuePrettyPrinter->print($parameter['defaultValue']);
        }

        return $label;
    }
}
