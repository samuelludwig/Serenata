<?php

namespace Serenata\Analysis\Relations;

use Serenata\Analysis\DocblockAnalyzer;

use Serenata\Parsing\DocblockParser;

/**
 * Base class for resolvers.
 */
abstract class AbstractResolver
{
    /**
     * @var DocblockAnalyzer
     */
    private $docblockAnalyzer;

    /**
     * @param DocblockAnalyzer $docblockAnalyzer
     */
    public function __construct(DocblockAnalyzer $docblockAnalyzer)
    {
        $this->docblockAnalyzer = $docblockAnalyzer;
    }

    /**
     * Returns a boolean indicating whether the specified item will inherit documentation from a parent item (if
     * present).
     *
     * @param array<string,mixed> $processedData
     *
     * @return bool
     */
    protected function isInheritingFullDocumentation(array $processedData): bool
    {
        return
            !$processedData['hasDocblock'] ||
            ($processedData['shortDescription'] && $this->docblockAnalyzer->isFullInheritDocSyntax(
                $processedData['shortDescription']
            ));
    }

    /**
     * Resolves the inheritDoc tag for the specified description.
     *
     * Note that according to phpDocumentor this only works for the long description (not the so-called 'summary' or
     * short description).
     *
     * @param string $description
     * @param string $parentDescription
     *
     * @return string
     */
    protected function resolveInheritDoc(string $description, string $parentDescription): string
    {
        return str_replace(DocblockParser::INHERITDOC, $parentDescription, $description);
    }

    /**
     * @param array<string,mixed> $propertyData
     *
     * @return array<string,mixed>
     */
    protected function extractInheritedPropertyInfo(array $propertyData): array
    {
        $inheritedKeys = [
            'hasDocumentation',
            'isDeprecated',
            'shortDescription',
            'longDescription',
            'typeDescription',
            'types',
        ];

        $info = [];

        foreach ($propertyData as $key => $value) {
            if (in_array($key, $inheritedKeys, true)) {
                $info[$key] = $value;
            }
        }

        return $info;
    }

    /**
     * @param array<string,mixed> $methodData
     * @param array<string,mixed> $inheritingMethodData
     *
     * @return array<string,mixed>
     */
    protected function extractInheritedMethodInfo(array $methodData, array $inheritingMethodData): array
    {
        $inheritedKeys = [
            'hasDocumentation',
            'isDeprecated',
            'shortDescription',
            'longDescription',
            'returnDescription',
            'returnTypes',
            'throws',
        ];

        // Normally parameters are inherited from the parent docblock. However, this causes problems when an overridden
        // method adds an additional optional parameter or a subclass constructor uses completely different parameters.
        // In either of these cases, we don't want to inherit the docblock parameters anymore, because it isn't
        // correct anymore (and the developer should specify a new docblock specifying the changed parameters).
        $inheritedMethodParameterNames = array_map(function (array $parameter) {
            return $parameter['name'];
        }, $methodData['parameters']);

        $inheritingMethodParameterNames = array_map(function (array $parameter) {
            return $parameter['name'];
        }, $inheritingMethodData['parameters']);

        // We need elements that are present in either A or B, but not in both. array_diff only returns items that
        // are present in A, but not in B.
        $parameterNameDiff1 = array_diff($inheritedMethodParameterNames, $inheritingMethodParameterNames);
        $parameterNameDiff2 = array_diff($inheritingMethodParameterNames, $inheritedMethodParameterNames);

        if (count($parameterNameDiff1) === 0 && count($parameterNameDiff2) === 0) {
            $inheritedKeys[] = 'parameters';
        }

        $info = [];

        foreach ($methodData as $key => $value) {
            if (in_array($key, $inheritedKeys, true)) {
                $info[$key] = $value;
            }
        }

        return $info;
    }
}
