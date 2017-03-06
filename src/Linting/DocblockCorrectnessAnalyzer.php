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
        return [
            'varTagMissing'         => $this->getVarTagMissingErrors(),
            'parameterMissing'      => $this->getParameterMissingErrors(),
            'parameterTypeMismatch' => $this->getParameterTypeMismatchErrors(),
            'superfluousParameter'  => $this->getSuperfluousParameterErrors()
        ];
    }

    /**
     * @inheritDoc
     */
    public function getWarnings(): array
    {
        return [
            'missingDocumentation'    => $this->getMissingDocumentationWarnings(),
            'deprecatedCategoryTag'   => $this->getDeprecatedCategoryTagWarnings(),
            'deprecatedSubpackageTag' => $this->getDeprecatedSubpackageTagWarnings(),
            'deprecatedLinkTag'       => $this->getDeprecatedLinkTagWarnings()
        ];
    }

    /**
     * @return array
     */
    protected function getVarTagMissingErrors(): array
    {
        $errors = [];

        foreach ($this->outlineIndexingVisitor->getStructures() as $structure) {
            $errors = array_merge($errors, $this->getVarTagMissingErrorsForStructure($structure));
        }

        return $errors;
    }

    /**
     * @param array $structure
     *
     * @return array
     */
    protected function getVarTagMissingErrorsForStructure(array $structure): array
    {
        $errors = [];

        foreach ($structure['properties'] as $property) {
            $errors = array_merge($errors, $this->getVarTagMissingErrorsForProperty($structure, $property));
        }

        foreach ($structure['constants'] as $constant) {
            $errors = array_merge($errors, $this->getVarTagMissingErrorsForClassConstant($structure, $constant));
        }

        return $errors;
    }

    /**
     * @param array $structure
     * @param array $property
     *
     * @return array
     */
    protected function getVarTagMissingErrorsForProperty(array $structure, array $property): array
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
     * @param array $constant
     *
     * @return array
     */
    protected function getVarTagMissingErrorsForClassConstant(array $structure, array $constant): array
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
     * @return array
     */
    protected function getParameterMissingErrors(): array
    {
        $errors = [];

        foreach ($this->outlineIndexingVisitor->getStructures() as $structure) {
            $errors = array_merge($errors, $this->getParameterMissingErrorsForStructure($structure));
        }

        foreach ($this->outlineIndexingVisitor->getGlobalFunctions() as $globalFunction) {
            $errors = array_merge($errors, $this->getParameterMissingErrorsForGlobalFunction($globalFunction));
        }

        return $errors;
    }

    /**
     * @param array $structure
     *
     * @return array
     */
    protected function getParameterMissingErrorsForStructure(array $structure): array
    {
        $errors = [];

        foreach ($structure['methods'] as $method) {
            $errors = array_merge($errors, $this->getParameterMissingErrorsForMethod($structure, $method));
        }

        return $errors;
    }

    /**
     * @param array $structure
     * @param array $method
     *
     * @return array
     */
    protected function getParameterMissingErrorsForMethod(array $structure, array $method): array
    {
        return $this->getParameterMissingErrorsForGlobalFunction($method);
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    protected function getParameterMissingErrorsForGlobalFunction(array $globalFunction): array
    {
        if (!$globalFunction['docComment']) {
           return [];
       }

       $result = $this->docblockParser->parse(
           $globalFunction['docComment'],
           [DocblockParser::DESCRIPTION, DocblockParser::PARAM_TYPE],
           $globalFunction['name']
       );

       if ($this->docblockAnalyzer->isFullInheritDocSyntax($result['descriptions']['short'])) {
           return [];
       }

       $docblockParameters = $result['params'];

       $issues = [];

       foreach ($globalFunction['parameters'] as $parameter) {
           $dollarName = '$' . $parameter['name'];

           if (isset($docblockParameters[$dollarName])) {
               continue;
           }

           $issues[] = [
               'name'      => $globalFunction['name'],
               'parameter' => $dollarName,
               'line'      => $globalFunction['startLine'],
               'start'     => $globalFunction['startPosName'],
               'end'       => $globalFunction['endPosName']
           ];
       }

       return $issues;
    }

    /**
     * @return array
     */
    protected function getParameterTypeMismatchErrors(): array
    {
        $errors = [];

        foreach ($this->outlineIndexingVisitor->getStructures() as $structure) {
            $errors = array_merge($errors, $this->getParameterTypeMismatchErrorsForStructure($structure));
        }

        foreach ($this->outlineIndexingVisitor->getGlobalFunctions() as $globalFunction) {
            $errors = array_merge($errors, $this->getParameterTypeMismatchErrorsForGlobalFunction($globalFunction));
        }

        return $errors;
    }

    /**
     * @param array $structure
     *
     * @return array
     */
    protected function getParameterTypeMismatchErrorsForStructure(array $structure): array
    {
        $errors = [];

        foreach ($structure['methods'] as $method) {
            $errors = array_merge($errors, $this->getParameterTypeMismatchErrorsForMethod($structure, $method));
        }

        return $errors;
    }

    /**
     * @param array $structure
     * @param array $method
     *
     * @return array
     */
    protected function getParameterTypeMismatchErrorsForMethod(array $structure, array $method): array
    {
        return $this->getParameterTypeMismatchErrorsForGlobalFunction($method);
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    protected function getParameterTypeMismatchErrorsForGlobalFunction(array $globalFunction): array
    {
        if (!$globalFunction['docComment']) {
            return [];
        }

        $result = $this->docblockParser->parse(
            $globalFunction['docComment'],
            [DocblockParser::DESCRIPTION, DocblockParser::PARAM_TYPE],
            $globalFunction['name']
        );

        if ($this->docblockAnalyzer->isFullInheritDocSyntax($result['descriptions']['short'])) {
            return [];
        }

        $docblockParameters = $result['params'];

        $issues = [];

        foreach ($globalFunction['parameters'] as $parameter) {
            $dollarName = '$' . $parameter['name'];

            if (!isset($docblockParameters[$dollarName]) || !$parameter['type']) {
                continue;
            }

            $parameterType = $parameter['type'];

            if ($parameter['isVariadic']) {
                $parameterType .= '[]';
            }

            $docblockType = $docblockParameters[$dollarName]['type'];
            $isTypeConformant = $this->typeAnalyzer->isTypeConformantWithDocblockType($parameterType, $docblockType);

            if ($isTypeConformant && $parameter['isReference'] === $docblockParameters[$dollarName]['isReference']) {
                continue;
            }

            $issues[] = [
                'name'      => $globalFunction['name'],
                'parameter' => $dollarName,
                'line'      => $globalFunction['startLine'],
                'start'     => $globalFunction['startPosName'],
                'end'       => $globalFunction['endPosName']
            ];
        }

        return $issues;
    }

    /**
     * @return array
     */
    protected function getSuperfluousParameterErrors(): array
    {
        $errors = [];

        foreach ($this->outlineIndexingVisitor->getStructures() as $structure) {
            $errors = array_merge($errors, $this->getSuperfluousParameterErrorsForStructure($structure));
        }

        foreach ($this->outlineIndexingVisitor->getGlobalFunctions() as $globalFunction) {
            $errors = array_merge($errors, $this->getSuperfluousParameterErrorsForGlobalFunction($globalFunction));
        }

        return $errors;
    }

    /**
     * @param array $structure
     *
     * @return array
     */
    protected function getSuperfluousParameterErrorsForStructure(array $structure): array
    {
        $errors = [];

        foreach ($structure['methods'] as $method) {
            $errors = array_merge($errors, $this->getSuperfluousParameterErrorsForMethod($structure, $method));
        }

        return $errors;
    }

    /**
     * @param array $structure
     * @param array $method
     *
     * @return array
     */
    protected function getSuperfluousParameterErrorsForMethod(array $structure, array $method): array
    {
        return $this->getSuperfluousParameterErrorsForGlobalFunction($method);
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    protected function getSuperfluousParameterErrorsForGlobalFunction(array $globalFunction): array
    {
        if (!$globalFunction['docComment']) {
            return [];
        }

        $result = $this->docblockParser->parse(
            $globalFunction['docComment'],
            [DocblockParser::DESCRIPTION, DocblockParser::PARAM_TYPE],
            $globalFunction['name']
        );

        if ($this->docblockAnalyzer->isFullInheritDocSyntax($result['descriptions']['short'])) {
            return [];
        }

        $keysFound = [];
        $docblockParameters = $result['params'];

        foreach ($globalFunction['parameters'] as $parameter) {
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
                'name'       => $globalFunction['name'],
                'parameters' => $superfluousParameterNames,
                'line'       => $globalFunction['startLine'],
                'start'      => $globalFunction['startPosName'],
                'end'        => $globalFunction['endPosName']
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getMissingDocumentationWarnings(): array
    {
        $warnings = [];

        foreach ($this->outlineIndexingVisitor->getStructures() as $structure) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForStructure($structure));
        }

        foreach ($this->outlineIndexingVisitor->getGlobalFunctions() as $globalFunction) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForGlobalFunction($globalFunction));
        }

        return $warnings;
    }

    /**
     * @return array
     */
    protected function getDeprecatedCategoryTagWarnings(): array
    {
        $warnings = [];

        foreach ($this->outlineIndexingVisitor->getStructures() as $structure) {
            $warnings = array_merge($warnings, $this->getDeprecatedCategoryTagWarningsForStructure($structure));
        }

        return $warnings;
    }

    /**
     * @return array
     */
    protected function getDeprecatedSubpackageTagWarnings(): array
    {
        $warnings = [];

        foreach ($this->outlineIndexingVisitor->getStructures() as $structure) {
            $warnings = array_merge($warnings, $this->getDeprecatedSubpackageTagWarningsForStructure($structure));
        }

        return $warnings;
    }

    /**
     * @return array
     */
    protected function getDeprecatedLinkTagWarnings(): array
    {
        $warnings = [];

        foreach ($this->outlineIndexingVisitor->getStructures() as $structure) {
            $warnings = array_merge($warnings, $this->getDeprecatedLinkTagWarningsForStructure($structure));
        }

        return $warnings;
    }

    /**
     * @param array $structure
     *
     * @return array
     */
    protected function getMissingDocumentationWarningsForStructure(array $structure): array
    {
        $warnings = [];

        $classInfo = $this->classlikeInfoBuilder->getClasslikeInfo($structure['fqcn']);

        if ($classInfo && !$classInfo['hasDocumentation']) {
            $warnings[] = [
                'name'  => $structure['name'],
                'line'  => $structure['startLine'],
                'start' => $structure['startPosName'],
                'end'   => $structure['endPosName']
            ];
        }

        foreach ($structure['methods'] as $method) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForMethod($structure, $method));
        }

        foreach ($structure['properties'] as $property) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForProperty($structure, $property));
        }

        foreach ($structure['constants'] as $constant) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForClassConstant($structure, $constant));
        }

        return $warnings;
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    protected function getMissingDocumentationWarningsForGlobalFunction(array $globalFunction): array
    {
        if ($globalFunction['docComment']) {
            return [];
        }

        return [
            [
                'name'  => $globalFunction['name'],
                'line'  => $globalFunction['startLine'],
                'start' => $globalFunction['startPosName'],
                'end'   => $globalFunction['endPosName']
            ]
        ];
    }

    /**
     * @param array $structure
     * @param array $method
     *
     * @return array
     */
    protected function getMissingDocumentationWarningsForMethod(array $structure, array $method): array
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
    protected function getMissingDocumentationWarningsForProperty(array $structure, array $property): array
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
    protected function getMissingDocumentationWarningsForClassConstant(array $structure, array $constant): array
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
     * @param array $structure
     *
     * @return array
     */
    protected function getDeprecatedCategoryTagWarningsForStructure(array $structure): array
    {
        $result = $this->docblockParser->parse($structure['docComment'], [
            DocblockParser::CATEGORY
        ], $structure['name']);

        if (!$result['category']) {
            return [];
        }

        return [
            [
                'name'  => $structure['name'],
                'line'  => $structure['startLine'],
                'start' => $structure['startPosName'],
                'end'   => $structure['endPosName']
            ]
        ];
    }

    /**
     * @param array $structure
     *
     * @return array
     */
    protected function getDeprecatedSubpackageTagWarningsForStructure(array $structure): array
    {
        $result = $this->docblockParser->parse($structure['docComment'], [
            DocblockParser::SUBPACKAGE
        ], $structure['name']);

        if (!$result['subpackage']) {
            return [];
        }

        return [
            [
                'name'  => $structure['name'],
                'line'  => $structure['startLine'],
                'start' => $structure['startPosName'],
                'end'   => $structure['endPosName']
            ]
        ];
    }

    /**
     * @param array $structure
     *
     * @return array
     */
    protected function getDeprecatedLinkTagWarningsForStructure(array $structure): array
    {
        $result = $this->docblockParser->parse($structure['docComment'], [
            DocblockParser::LINK
        ], $structure['name']);

        if (!$result['link']) {
            return [];
        }

        return [
            [
                'name'  => $structure['name'],
                'line'  => $structure['startLine'],
                'start' => $structure['startPosName'],
                'end'   => $structure['endPosName']
            ]
        ];
    }
}
