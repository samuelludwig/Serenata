<?php

namespace PhpIntegrator\Tooltips;

/**
 * Generates tooltips for functions.
 */
class FunctionTooltipGenerator
{
    use TooltipGenerationTrait;

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
    protected function generateSummary(array $functionInfo): string
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
    protected function generateLongDescription(array $functionInfo): ?string
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
    protected function generateParameters(array $functionInfo): ?string
    {
        $parameterLines = [];

        if (empty($functionInfo['parameters'])) {
            return null;
        }

        foreach ($functionInfo['parameters'] as $parameter) {
            $parameterColumns = [];

            $name = '';
            $name .= '• ';

            if ($parameter['isOptional']) {
                $name .= '[';
            }

            if ($parameter['isReference']) {
                $name .= '&';
            }

            if ($parameter['isVariadic']) {
                $name = '...';
            }

            $name .= '$' . $parameter['name'];

            if ($parameter['isOptional']) {
                $name .= ']';
            }

            $parameterColumns[] = '**' . $name . '**';

            if (!empty($parameter['types'])) {
                $parameterColumns[] = '*' . $this->getTypeStringForTypeArray($parameter['types']) . '*';
            } else {
                $parameterColumns[] = ' ';
            }

            if ($parameter['description']) {
                $parameterColumns[] = $parameter['description'];
            } else {
                $parameterColumns[] = ' ';
            }

            $parameterLines[] = implode(' | ', $parameterColumns);
        }

        // The header symbols seem to be required for some markdown parser, such as npm's marked.
        $table =
            "   |   |   \n" .
            "--- | --- | ---\n" .
            implode("\n", $parameterLines);

        return "# Parameters\n" . $table;
    }

    /**
     * @param array $functionInfo
     *
     * @return string
     */
    protected function generateReturn(array $functionInfo): string
    {
        $returnDescription = null;

        if (!empty($functionInfo['returnTypes'])) {
            $returnDescription = '*' . $this->getTypeStringForTypeArray($functionInfo['returnTypes']) . '*';

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
    protected function generateThrows(array $functionInfo): ?string
    {
        $throwsLines = [];

        foreach ($functionInfo['throws'] as $exceptionType => $thrownWhen) {
            $throwsColumns = [];

            $throwsColumns[] = "• **{$exceptionType}**";

            if ($thrownWhen) {
                $throwsColumns[] = $thrownWhen;
            } else {
                $throwsColumns[] = ' ';
            }

            $throwsLines[] = implode(' | ', $throwsColumns);
        }

        if (empty($throwsLines)) {
            return null;
        }

        // The header symbols seem to be required for some markdown parser, such as npm's marked.
        $table =
            "   |   |   \n" .
            "--- | --- | ---\n" .
            implode("\n", $throwsLines);

        return "# Throws\n" . $table;
    }
}
