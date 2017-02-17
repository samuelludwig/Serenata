<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

use PhpIntegrator\Analysis\GlobalFunctionsProvider;

/**
 * Generates tooltips for functions.
 */
class FunctionTooltipGenerator
{
    /**
     * @var GlobalFunctionsProvider
     */
    protected $globalFunctionsProvider;

    /**
     * @param GlobalFunctionsProvider $globalFunctionsProvider
     */
    public function __construct(GlobalFunctionsProvider $globalFunctionsProvider)
    {
        $this->globalFunctionsProvider = $globalFunctionsProvider;
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @throws UnexpectedValueException when the function was not found.
     *
     * @return string
     */
    public function generate(string $fullyQualifiedName): string
    {
        $functionInfo = $this->getFunctionInfo($fullyQualifiedName);

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
            $throwsLines[] = trim("• **{$exceptionType}** {$thrownWhen}");
        }

        if (empty($throwsLines)) {
            return null;
        }

        return "# Throws\n" . implode("\n", $throwsLines);
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    protected function getFunctionInfo(string $fullyQualifiedName): array
    {
        $functions = $this->globalFunctionsProvider->getAll();

        if (!isset($functions[$fullyQualifiedName])) {
            throw new UnexpectedValueException('No data found for function with name ' . $fullyQualifiedName);
        }

        return $functions[$fullyQualifiedName];
    }

    /**
     * @param array $typeArray
     *
     * @return string
     */
    protected function getTypeStringForTypeArray(array $typeArray): string
    {
        if (empty($typeArray)) {
            return '(Not known)';
        }

        $typeList = [];

        foreach ($typeArray as $type) {
            $typeList[] = $type['type'];
        }

        return implode('|', $typeList);
    }
}
