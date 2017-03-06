<?php

namespace PhpIntegrator\Tooltips;

/**
 * Generates tooltips for properties.
 */
class PropertyTooltipGenerator
{
    use TooltipGenerationTrait;

    /**
     * @param array $info
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
     * @param array $info
     *
     * @return string
     */
    protected function generateSummary(array $info): string
    {
        if ($info['shortDescription']) {
            return $info['shortDescription'];
        }

        return '(No documentation available)';
    }

    /**
     * @param array $info
     *
     * @return string|null
     */
    protected function generateLongDescription(array $info): ?string
    {
        if (!empty($info['longDescription'])) {
            return "# Description\n" . $info['longDescription'];
        }

        return null;
    }

    /**
     * @param array $info
     *
     * @return string
     */
    protected function generateType(array $info): string
    {
        $returnDescription = null;

        if (!empty($info['types'])) {
            $returnDescription = '*' . $this->getTypeStringForTypeArray($info['types']) . '*';

            if ($info['typeDescription']) {
                $returnDescription .= ' &mdash; ' . $info['typeDescription'];
            }
        } else {
            $returnDescription = '(Not known)';
        }

        return "# Type\n{$returnDescription}";
    }
}