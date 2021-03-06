<?php

namespace Serenata\PrettyPrinting;

/**
 * Pretty prints function and method parameters.
 */
final class FunctionParameterPrettyPrinter
{
    /**
     * @var ParameterDefaultValuePrettyPrinter
     */
    private $parameterDefaultValuePrettyPrinter;

    /**
     * @var TypeListPrettyPrinter
     */
    private $typeListPrettyPrinter;

    /**
     * @var ParameterNamePrettyPrinter
     */
    private $parameterNamePrettyPrinter;

    /**
     * @param ParameterDefaultValuePrettyPrinter $parameterDefaultValuePrettyPrinter
     * @param TypeListPrettyPrinter              $typeListPrettyPrinter
     * @param ParameterNamePrettyPrinter         $parameterNamePrettyPrinter
     */
    public function __construct(
        ParameterDefaultValuePrettyPrinter $parameterDefaultValuePrettyPrinter,
        TypeListPrettyPrinter $typeListPrettyPrinter,
        ParameterNamePrettyPrinter $parameterNamePrettyPrinter
    ) {
        $this->parameterDefaultValuePrettyPrinter = $parameterDefaultValuePrettyPrinter;
        $this->typeListPrettyPrinter = $typeListPrettyPrinter;
        $this->parameterNamePrettyPrinter = $parameterNamePrettyPrinter;
    }

    /**
     * @param array<string,mixed> $parameter
     *
     * @return string
     */
    public function print(array $parameter): string
    {
        $label = '';

        if (count($parameter['types']) > 0) {
            $label .= $this->typeListPrettyPrinter->print(array_map(function (array $type): string {
                return $this->getClassNameFromFqcn($type['type']);
            }, $parameter['types']));

            $label .= ' ';
        }

        $label .= $this->parameterNamePrettyPrinter->print($parameter);

        if ($parameter['defaultValue'] !== null) {
            $label .= ' = ' . $this->parameterDefaultValuePrettyPrinter->print($parameter['defaultValue']);
        }

        return $label;
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    private function getClassNameFromFqcn(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return array_pop($parts);
    }
}
