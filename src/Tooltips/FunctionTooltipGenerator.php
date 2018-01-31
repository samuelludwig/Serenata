<?php

namespace PhpIntegrator\Tooltips;

use PhpIntegrator\PrettyPrinting\ParameterNamePrettyPrinter;

/**
 * Generates tooltips for functions.
 */
class FunctionTooltipGenerator
{
    /**
     * @var ParameterNamePrettyPrinter
     */
    private $parameterNamePrettyPrinter;

    /**
     * @var TooltipTypeListPrettyPrinter
     */
    private $tooltipTypeListPrettyPrinter;

    /**
     * @param ParameterNamePrettyPrinter   $parameterNamePrettyPrinter
     * @param TooltipTypeListPrettyPrinter $tooltipTypeListPrettyPrinter
     */
    public function __construct(
        ParameterNamePrettyPrinter $parameterNamePrettyPrinter,
        TooltipTypeListPrettyPrinter $tooltipTypeListPrettyPrinter
    ) {
        $this->parameterNamePrettyPrinter = $parameterNamePrettyPrinter;
        $this->tooltipTypeListPrettyPrinter = $tooltipTypeListPrettyPrinter;
    }

    /**
     * @param array $functionInfo
     *
     * @return string
     */
    public function generate(array $functionInfo): string
    {
        $sections = [
            $this->generateSummary($functionInfo),
            $this->generateLongDescription($functionInfo),
            $this->generateParameters($functionInfo),
            $this->generateReturn($functionInfo),
            $this->generateThrows($functionInfo)
        ];

        return implode("\n\n", array_filter($sections));
    }

    /**
     * @param array $functionInfo
     *
     * @return string
     */
    private function generateSummary(array $functionInfo): string
    {
        if ($functionInfo['shortDescription']) {
            return $functionInfo['shortDescription'];
        }

        return '(No documentation available)';
    }

    /**
     * @param array $functionInfo
     *
     * @return string|null
     */
    private function generateLongDescription(array $functionInfo): ?string
    {
        if (!empty($functionInfo['longDescription'])) {
            return "# Description\n" . $functionInfo['longDescription'];
        }

        return null;
    }

    /**
     * @param array $functionInfo
     *
     * @return string|null
     */
    private function generateParameters(array $functionInfo): ?string
    {
        $parameterLines = [];

        if (empty($functionInfo['parameters'])) {
            return null;
        }

        foreach ($functionInfo['parameters'] as $parameter) {
            $parameterLines[] = $this->generateParameter($parameter);
        }

        return "# Parameters\n" . implode("\n", $parameterLines);
    }

    /**
     * @param array $parameter
     *
     * @return string
     */
    private function generateParameter(array $parameter): string
    {
        $text = '';

        if ($parameter['isOptional']) {
            $text .= '[';
        }

        $text .= $this->parameterNamePrettyPrinter->print($parameter);

        if ($parameter['isOptional']) {
            $text .= ']';
        }

        $text = '#### • **' . $text . '**';

        if (!empty($parameter['types'])) {
            $value = $this->tooltipTypeListPrettyPrinter->print(array_map(function (array $type) {
                return $type['type'];
            }, $parameter['types']));

            $text .= ' — *' . $value . '*';
        }

        $text .= "\n";

        if ($parameter['description']) {
            $text .= ($parameter['description']);
        } else {
            $text .= '(No documentation available)';
        }

        return $text . "\n";
    }

    /**
     * @param array $functionInfo
     *
     * @return string
     */
    private function generateReturn(array $functionInfo): string
    {
        $returnDescription = null;

        if (!empty($functionInfo['returnTypes'])) {
            $value = $this->tooltipTypeListPrettyPrinter->print(array_map(function (array $type) {
                return $type['type'];
            }, $functionInfo['returnTypes']));

            $returnDescription = '*' . $value . '*';

            if ($functionInfo['returnDescription']) {
                $returnDescription .= ' &mdash; ' . $functionInfo['returnDescription'];
            }
        } else {
            $returnDescription = '(Not known)';
        }

        return "# Returns\n{$returnDescription}";
    }

    /**
     * @param array $functionInfo
     *
     * @return string|null
     */
    private function generateThrows(array $functionInfo): ?string
    {
        $throwsLines = [];

        foreach ($functionInfo['throws'] as $throwsItem) {
            $text = "#### • **{$throwsItem['type']}**\n";

            if ($throwsItem['description']) {
                $text .= $throwsItem['description'];
            } else {
                $text .= '(No context available)';
            }

            $throwsLines[] = $text . "\n";
        }

        if (empty($throwsLines)) {
            return null;
        }

        return "# Throws\n" . implode("\n", $throwsLines);
    }
}
