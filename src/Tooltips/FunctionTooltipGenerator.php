<?php

namespace Serenata\Tooltips;

use LogicException;

use Serenata\PrettyPrinting\ParameterNamePrettyPrinter;

/**
 * Generates tooltips for functions.
 */
final class FunctionTooltipGenerator
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
     * @param array<string,mixed> $functionInfo
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
            $this->generateThrows($functionInfo),
        ];

        return implode("\n\n", array_filter($sections));
    }

    /**
     * @param array<string,mixed> $functionInfo
     *
     * @return string
     */
    private function generateSummary(array $functionInfo): string
    {
        if ($functionInfo['shortDescription'] !== '' && $functionInfo['shortDescription'] !== null) {
            return $functionInfo['shortDescription'];
        }

        return '(No documentation available)';
    }

    /**
     * @param array<string,mixed> $functionInfo
     *
     * @return string|null
     */
    private function generateLongDescription(array $functionInfo): ?string
    {
        if ($functionInfo['longDescription'] !== '' && $functionInfo['longDescription'] !== null) {
            return "# Description\n" . $functionInfo['longDescription'];
        }

        return null;
    }

    /**
     * @param array<string,mixed> $functionInfo
     *
     * @return string|null
     */
    private function generateParameters(array $functionInfo): ?string
    {
        $parameterLines = [];

        if (count($functionInfo['parameters']) === 0) {
            return null;
        }

        foreach ($functionInfo['parameters'] as $parameter) {
            $parameterLines[] = $this->generateParameter($parameter);
        }

        return "# Parameters\n" . implode("\n", $parameterLines);
    }

    /**
     * @param array<string,mixed> $parameter
     *
     * @return string
     */
    private function generateParameter(array $parameter): string
    {
        $text = '';

        if ($parameter['isOptional'] === true) {
            $text .= '[';
        }

        $text .= $this->parameterNamePrettyPrinter->print($parameter);

        if ($parameter['isOptional'] === true) {
            $text .= ']';
        }

        $text = '#### • **' . $text . '**';

        if (count($parameter['types']) > 0) {
            $value = $this->tooltipTypeListPrettyPrinter->print(array_map(function (array $type): string {
                return $this->getClassNameFromFqcn($type['type']);
            }, $parameter['types']));

            $text .= ' — *' . $value . '*';
        }

        $text .= "\n";

        if ($parameter['description'] !== '' &&$parameter['description'] !== null) {
            $text .= ($parameter['description']);
        } else {
            $text .= '(No documentation available)';
        }

        return $text . "\n";
    }

    /**
     * @param array<string,mixed> $functionInfo
     *
     * @return string
     */
    private function generateReturn(array $functionInfo): string
    {
        $returnDescription = null;

        if (count($functionInfo['returnTypes']) > 0) {
            $value = $this->tooltipTypeListPrettyPrinter->print(array_map(function (array $type): string {
                return $this->getClassNameFromFqcn($type['type']);
            }, $functionInfo['returnTypes']));

            $returnDescription = '*' . $value . '*';

            if ($functionInfo['returnDescription'] !== '' && $functionInfo['returnDescription'] !== null) {
                $returnDescription .= ' &mdash; ' . $functionInfo['returnDescription'];
            }
        } else {
            $returnDescription = '(Not known)';
        }

        return "# Returns\n{$returnDescription}";
    }

    /**
     * @param array<string,mixed> $functionInfo
     *
     * @return string|null
     */
    private function generateThrows(array $functionInfo): ?string
    {
        $throwsLines = [];

        foreach ($functionInfo['throws'] as $throwsItem) {
            $type = $this->getClassNameFromFqcn($throwsItem['type']);

            $text = "#### • **{$type}**\n";

            if ($throwsItem['description'] !== '' && $throwsItem['description'] !== null) {
                $text .= $throwsItem['description'];
            } else {
                $text .= '(No context available)';
            }

            $throwsLines[] = $text . "\n";
        }

        if (count($throwsLines) === 0) {
            return null;
        }

        return "# Throws\n" . implode("\n", $throwsLines);
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    private function getClassNameFromFqcn(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        $part = array_pop($parts);

        if ($part === null) {
            throw new LogicException('FQCN "' . $fqcn . '" does not contain at least one part');
        }

        return $part;
    }
}
