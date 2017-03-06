<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\DocblockAnalyzer;
use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Visiting\OutlineFetchingVisitor;

use PhpIntegrator\Parsing\DocblockParser;

/**
 * Analyzes the correctness of docblocks.
 */
class DocblockCorrectnessAnalyzer implements AnalyzerInterface
{
    /**
     * @var OutlineFetchingVisitor
     */
    protected $outlineIndexingVisitor;

    /**
     * @var DocblockParser
     */
    protected $docblockParser;

    /**
     * @var TypeAnalyzer
     */
    protected $typeAnalyzer;

    /**
     * @var DocblockAnalyzer
     */
    protected $docblockAnalyzer;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @param string               $code
     * @param ClasslikeInfoBuilder $classlikeInfoBuilder
     * @param DocblockParser       $docblockParser
     * @param TypeAnalyzer         $typeAnalyzer
     * @param DocblockAnalyzer     $docblockAnalyzer
     */
    public function __construct(
        string $code,
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        DocblockAnalyzer $docblockAnalyzer
    ) {
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->docblockParser = $docblockParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockAnalyzer = $docblockAnalyzer;

        $this->outlineIndexingVisitor = new OutlineFetchingVisitor($typeAnalyzer, $code);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'docblockIssues';
    }

    /**
     * @inheritDoc
     */
    public function getVisitors(): array
    {
        return [
            $this->outlineIndexingVisitor
        ];
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getWarnings(): array
    {
        $docblockIssues = [
            'varTagMissing'           => [],
            'missingDocumentation'    => [],
            'parameterMissing'        => [],
            'parameterTypeMismatch'   => [],
            'superfluousParameter'    => [],
            'deprecatedCategoryTag'   => [],
            'deprecatedSubpackageTag' => [],
            'deprecatedLinkTag'       => []
        ];

        $structures = $this->outlineIndexingVisitor->getStructures();

        foreach ($structures as $structure) {
            $docblockIssues = array_merge_recursive(
                $docblockIssues,
                $this->analyzeStructureDocblock($structure)
            );

            foreach ($structure['methods'] as $method) {
                $docblockIssues = array_merge_recursive(
                    $docblockIssues,
                    $this->analyzeMethodDocblock($structure, $method)
                );
            }

            foreach ($structure['properties'] as $property) {
                $docblockIssues = array_merge_recursive(
                    $docblockIssues,
                    $this->analyzePropertyDocblock($structure, $property)
                );
            }

            foreach ($structure['constants'] as $constant) {
                $docblockIssues = array_merge_recursive(
                    $docblockIssues,
                    $this->analyzeClassConstantDocblock($structure, $constant)
                );
            }
        }

        $globalFunctions = $this->outlineIndexingVisitor->getGlobalFunctions();

        foreach ($globalFunctions as $function) {
            $docblockIssues = array_merge_recursive(
                $docblockIssues,
                $this->analyzeFunctionDocblock($function)
            );
        }

        return $docblockIssues;
    }

    /**
     * @param array $structure
     *
     * @return array
     */
    protected function analyzeStructureDocblock(array $structure): array
    {
        $docblockIssues = [
            'missingDocumentation'    => [],
            'deprecatedCategoryTag'   => [],
            'deprecatedSubpackageTag' => [],
            'deprecatedLinkTag'       => []
        ];

        if ($structure['docComment']) {
            $result = $this->docblockParser->parse($structure['docComment'], [
                DocblockParser::CATEGORY,
                DocblockParser::SUBPACKAGE,
                DocblockParser::LINK
            ], $structure['name']);

            if ($result['category']) {
                $docblockIssues['deprecatedCategoryTag'][] = [
                    'name'  => $structure['name'],
                    'line'  => $structure['startLine'],
                    'start' => $structure['startPosName'],
                    'end'   => $structure['endPosName']
                ];
            }

            if ($result['subpackage']) {
                $docblockIssues['deprecatedSubpackageTag'][] = [
                    'name'  => $structure['name'],
                    'line'  => $structure['startLine'],
                    'start' => $structure['startPosName'],
                    'end'   => $structure['endPosName']
                ];
            }

            if ($result['link']) {
                $docblockIssues['deprecatedLinkTag'][] = [
                    'name'  => $structure['name'],
                    'line'  => $structure['startLine'],
                    'start' => $structure['startPosName'],
                    'end'   => $structure['endPosName']
                ];
            }

            return $docblockIssues;
        }

        $classInfo = $this->classlikeInfoBuilder->getClasslikeInfo($structure['fqcn']);

        if ($classInfo && !$classInfo['hasDocumentation']) {
            $docblockIssues['missingDocumentation'][] = [
                'name'  => $structure['name'],
                'line'  => $structure['startLine'],
                'start' => $structure['startPosName'],
                'end'   => $structure['endPosName']
            ];
        }

        return $docblockIssues;
    }

    /**
     * @param array $structure
     * @param array $method
     *
     * @return array
     */
    protected function analyzeMethodDocblock(array $structure, array $method): array
    {
        $issues = $this->analyzeFunctionDocblock($method);
        $issues['missingDocumentation'] = $this->analyzeMethodDocblockMissingDocumentation($structure, $method);

        return $issues;
    }

    /**
     * @param array $structure
     * @param array $method
     *
     * @return array
     */
    protected function analyzeMethodDocblockMissingDocumentation(array $structure, array $method): array
    {
        if ($method['docComment']) {
            return [];
        }

        $classInfo = $this->classlikeInfoBuilder->getClasslikeInfo($structure['fqcn']);

        if (!$classInfo ||
            !isset($classInfo['methods'][$method['name']]) ||
            $classInfo['methods'][$method['name']]['hasDocumentation']
        ) {
            return [];
        }

        return [
            [
                'name'  => $method['name'],
                'line'  => $method['startLine'],
                'start' => $method['startPosName'],
                'end'   => $method['endPosName']
            ]
        ];
    }

    /**
     * @param array $structure
     * @param array $property
     *
     * @return array
     */
    protected function analyzePropertyDocblock(array $structure, array $property): array
    {
        return [
            'varTagMissing'        => $this->analyzePropertyDocblockVarTagMissing($structure, $property),
            'missingDocumentation' => $this->analyzePropertyDocblockMissingDocumentation($structure, $property)
        ];
    }

    /**
     * @param array $structure
     * @param array $property
     *
     * @return array
     */
    protected function analyzePropertyDocblockVarTagMissing(array $structure, array $property): array
    {
        if (!$property['docComment']) {
            return [];
        }

        $result = $this->docblockParser->parse($property['docComment'], [DocblockParser::VAR_TYPE], $property['name']);

        if (isset($result['var']['$' . $property['name']]['type'])) {
            return [];
        }

        return [
            [
                'name'  => $property['name'],
                'line'  => $property['startLine'],
                'start' => $property['startPosName'],
                'end'   => $property['endPosName']
            ]
        ];
    }

    /**
     * @param array $structure
     * @param array $property
     *
     * @return array
     */
    protected function analyzePropertyDocblockMissingDocumentation(array $structure, array $property): array
    {
        if ($property['docComment']) {
            return [];
        }

        $classInfo = $this->classlikeInfoBuilder->getClasslikeInfo($structure['fqcn']);

        if (!$classInfo ||
            !isset($classInfo['properties'][$property['name']]) ||
            $classInfo['properties'][$property['name']]['hasDocumentation']
        ) {
            return [];
        }

        return [
            [
                'name'  => $property['name'],
                'line'  => $property['startLine'],
                'start' => $property['startPosName'],
                'end'   => $property['endPosName']
            ]
        ];
    }

    /**
     * @param array $structure
     * @param array $constant
     *
     * @return array
     */
    protected function analyzeClassConstantDocblock(array $structure, array $constant): array
    {
        return [
            'varTagMissing'        => $this->analyzeClassConstantDocblockVarTagMissing($structure, $constant),
            'missingDocumentation' => $this->analyzeClassConstantDocblockMissingDocumentation($structure, $constant)
        ];
    }

    /**
     * @param array $structure
     * @param array $constant
     *
     * @return array
     */
    protected function analyzeClassConstantDocblockVarTagMissing(array $structure, array $constant): array
    {
        if (!$constant['docComment']) {
            return [];
        }

        $result = $this->docblockParser->parse($constant['docComment'], [DocblockParser::VAR_TYPE], $constant['name']);

        if (isset($result['var']['$' . $constant['name']]['type'])) {
            return [];
        }

        return [
            [
                'name'  => $constant['name'],
                'line'  => $constant['startLine'],
                'start' => $constant['startPosName'],
                'end'   => $constant['endPosName'] + 1
            ]
        ];
    }

    /**
     * @param array $structure
     * @param array $constant
     *
     * @return array
     */
    protected function analyzeClassConstantDocblockMissingDocumentation(array $structure, array $constant): array
    {
        if ($constant['docComment']) {
            return [];
        }

        return [
            [
                'name'  => $constant['name'],
                'line'  => $constant['startLine'],
                'start' => $constant['startPosName'],
                'end'   => $constant['endPosName']
            ]
        ];
    }

    /**
     * @param array $function
     *
     * @return array
     */
    protected function analyzeFunctionDocblock(array $function): array
    {
        return [
            'missingDocumentation'  => $this->analyzeFunctionDocblockMissingDocumentation($function),
            'parameterMissing'      => $this->analyzeFunctionDocblockParameterMissing($function),
            'parameterTypeMismatch' => $this->analyzeFunctionDocblockParameterTypeMismatches($function),
            'superfluousParameter'  => $this->analyzeFunctionDocblockSuperfluousParameters($function)
        ];
    }

    /**
     * @param array $function
     *
     * @return array
     */
    protected function analyzeFunctionDocblockMissingDocumentation(array $function): array
    {
        if ($function['docComment']) {
            return [];
        }

        return [
            [
                'name'  => $function['name'],
                'line'  => $function['startLine'],
                'start' => $function['startPosName'],
                'end'   => $function['endPosName']
            ]
        ];
    }

    /**
     * @param array $function
     *
     * @return array
     */
    protected function analyzeFunctionDocblockParameterMissing(array $function): array
    {
        if (!$function['docComment']) {
            return [];
        }

        $result = $this->docblockParser->parse(
            $function['docComment'],
            [DocblockParser::DESCRIPTION, DocblockParser::PARAM_TYPE],
            $function['name']
        );

        if ($this->docblockAnalyzer->isFullInheritDocSyntax($result['descriptions']['short'])) {
            return [];
        }

        $docblockParameters = $result['params'];

        $issues = [];

        foreach ($function['parameters'] as $parameter) {
            $dollarName = '$' . $parameter['name'];

            if (isset($docblockParameters[$dollarName])) {
                continue;
            }

            $issues[] = [
                'name'      => $function['name'],
                'parameter' => $dollarName,
                'line'      => $function['startLine'],
                'start'     => $function['startPosName'],
                'end'       => $function['endPosName']
            ];
        }

        return $issues;
    }

    /**
     * @param array $function
     *
     * @return array
     */
    protected function analyzeFunctionDocblockParameterTypeMismatches(array $function): array
    {
        if (!$function['docComment']) {
            return [];
        }

        $result = $this->docblockParser->parse(
            $function['docComment'],
            [DocblockParser::DESCRIPTION, DocblockParser::PARAM_TYPE],
            $function['name']
        );

        if ($this->docblockAnalyzer->isFullInheritDocSyntax($result['descriptions']['short'])) {
            return [];
        }

        $docblockParameters = $result['params'];

        $issues = [];

        foreach ($function['parameters'] as $parameter) {
            $dollarName = '$' . $parameter['name'];

            if (!isset($docblockParameters[$dollarName]) || !$parameter['type']) {
                continue;
            }

            $parameterType = $parameter['type'];

            if ($parameter['isVariadic']) {
                $parameterType .= '[]';
            }

            $docblockType = $docblockParameters[$dollarName]['type'];

            if ($this->typeAnalyzer->isTypeConformantWithDocblockType($parameterType, $docblockType) &&
                $parameter['isReference'] === $docblockParameters[$dollarName]['isReference']
            ) {
                continue;
            }

            $issues[] = [
                'name'      => $function['name'],
                'parameter' => $dollarName,
                'line'      => $function['startLine'],
                'start'     => $function['startPosName'],
                'end'       => $function['endPosName']
            ];
        }

        return $issues;
    }

    /**
     * @param array $function
     *
     * @return array
     */
    protected function analyzeFunctionDocblockSuperfluousParameters(array $function): array
    {
        if (!$function['docComment']) {
            return [];
        }

        $result = $this->docblockParser->parse(
            $function['docComment'],
            [DocblockParser::DESCRIPTION, DocblockParser::PARAM_TYPE],
            $function['name']
        );

        if ($this->docblockAnalyzer->isFullInheritDocSyntax($result['descriptions']['short'])) {
            return [];
        }

        $keysFound = [];
        $docblockParameters = $result['params'];

        foreach ($function['parameters'] as $parameter) {
            $dollarName = '$' . $parameter['name'];

            if (isset($docblockParameters[$dollarName])) {
                $keysFound[] = $dollarName;
            }
        }

        $superfluousParameterNames = array_values(array_diff(array_keys($docblockParameters), $keysFound));

        if (empty($superfluousParameterNames)) {
            return [];
        }

        return [
            [
                'name'       => $function['name'],
                'parameters' => $superfluousParameterNames,
                'line'       => $function['startLine'],
                'start'      => $function['startPosName'],
                'end'        => $function['endPosName']
            ]
        ];
    }
}
