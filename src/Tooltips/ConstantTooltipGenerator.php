<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

use PhpIntegrator\Analysis\GlobalConstantsProvider;

/**
 * Generates tooltips for constants.
 */
class ConstantTooltipGenerator
{
    use TooltipGenerationTrait;

    /**
     * @var GlobalConstantsProvider
     */
    protected $globalConstantsProvider;

    /**
     * @param GlobalConstantsProvider $globalConstantsProvider
     */
    public function __construct(GlobalConstantsProvider $globalConstantsProvider)
    {
        $this->globalConstantsProvider = $globalConstantsProvider;
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
        $info = $this->getConstantInfo($fullyQualifiedName);

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

    /**
     * @param string $fullyQualifiedName
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    protected function getConstantInfo(string $fullyQualifiedName): array
    {
        $functions = $this->globalConstantsProvider->getAll();

        if (!isset($functions[$fullyQualifiedName])) {
            throw new UnexpectedValueException('No data found for function with name ' . $fullyQualifiedName);
        }

        return $functions[$fullyQualifiedName];
    }
}
