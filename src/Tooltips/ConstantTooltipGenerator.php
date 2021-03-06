<?php

namespace Serenata\Tooltips;

/**
 * Generates tooltips for constants.
 */
final class ConstantTooltipGenerator
{
    /**
     * @var TooltipTypeListPrettyPrinter
     */
    private $tooltipTypeListPrettyPrinter;

    /**
     * @param TooltipTypeListPrettyPrinter $tooltipTypeListPrettyPrinter
     */
    public function __construct(TooltipTypeListPrettyPrinter $tooltipTypeListPrettyPrinter)
    {
        $this->tooltipTypeListPrettyPrinter = $tooltipTypeListPrettyPrinter;
    }

    /**
     * @param array<string,mixed> $info
     *
     * @return string
     */
    public function generate(array $info): string
    {
        $sections = [
            $this->generateSummary($info),
            $this->generateLongDescription($info),
            $this->generateType($info),
        ];

        return implode("\n\n", array_filter($sections));
    }

    /**
     * @param array<string,mixed> $info
     *
     * @return string
     */
    private function generateSummary(array $info): string
    {
        if ($info['shortDescription'] !== '' && $info['shortDescription'] !== null) {
            return $info['shortDescription'];
        }

        return '(No documentation available)';
    }

    /**
     * @param array<string,mixed> $info
     *
     * @return string|null
     */
    private function generateLongDescription(array $info): ?string
    {
        if ($info['longDescription'] !== '' && $info['longDescription'] !== null) {
            return "# Description\n" . $info['longDescription'];
        }

        return null;
    }

    /**
     * @param array<string,mixed> $info
     *
     * @return string
     */
    private function generateType(array $info): string
    {
        $returnDescription = null;

        if (count($info['types']) > 0) {
            $value = $this->tooltipTypeListPrettyPrinter->print(array_map(function (array $type) {
                return $type['type'];
            }, $info['types']));

            $returnDescription = '*' . $value . '*';

            if ($info['typeDescription'] !== '' && $info['typeDescription'] !== null) {
                $returnDescription .= ' &mdash; ' . $info['typeDescription'];
            }
        } else {
            $returnDescription = '(Not known)';
        }

        return "# Type\n{$returnDescription}";
    }
}
