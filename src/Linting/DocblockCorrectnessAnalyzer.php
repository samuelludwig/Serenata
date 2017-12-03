<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\DocblockAnalyzer;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\ParameterDocblockTypeSemanticEqualityChecker;

use PhpIntegrator\Analysis\Visiting\OutlineFetchingVisitor;

use PhpIntegrator\Parsing\DocblockParser;

/**
 * Analyzes the correctness of docblocks.
 */
final class DocblockCorrectnessAnalyzer implements AnalyzerInterface
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var ParameterDocblockTypeSemanticEqualityChecker
     */
    private $parameterDocblockTypeSemanticEqualityChecker;

    /**
     * @var OutlineFetchingVisitor
     */
    private $outlineIndexingVisitor;

    /**
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var DocblockAnalyzer
     */
    private $docblockAnalyzer;

    /**
    * @param string                                        $file
     * @param string                                       $code
     * @param ParameterDocblockTypeSemanticEqualityChecker $parameterDocblockTypeSemanticEqualityChecker
     * @param DocblockParser                               $docblockParser
     * @param TypeAnalyzer                                 $typeAnalyzer
     * @param DocblockAnalyzer                             $docblockAnalyzer
     */
    public function __construct(
        string $file,
        string $code,
        ParameterDocblockTypeSemanticEqualityChecker $parameterDocblockTypeSemanticEqualityChecker,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        DocblockAnalyzer $docblockAnalyzer
    ) {
        $this->file = $file;
        $this->parameterDocblockTypeSemanticEqualityChecker = $parameterDocblockTypeSemanticEqualityChecker;
        $this->docblockParser = $docblockParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockAnalyzer = $docblockAnalyzer;

        $this->outlineIndexingVisitor = new OutlineFetchingVisitor($typeAnalyzer, $code);
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
        return array_merge(
            $this->getVarTagMissingErrors(),
            $this->getParameterMissingErrors(),
            $this->getParameterTypeMismatchErrors(),
            $this->getReturnTypeMismatchErrors(),
            $this->getSuperfluousParameterErrors()
        );
    }

    /**
     * @inheritDoc
     */
    public function getWarnings(): array
    {
        return array_merge(
            $this->getDeprecatedCategoryTagWarnings(),
            $this->getDeprecatedSubpackageTagWarnings(),
            $this->getDeprecatedLinkTagWarnings()
        );
    }

    /**
     * @return array
     */
    private function getVarTagMissingErrors(): array
    {
        $errors = [];

        foreach ($this->outlineIndexingVisitor->getClasslikes() as $classlike) {
            $errors = array_merge($errors, $this->getVarTagMissingErrorsForStructure($classlike));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     *
     * @return array
     */
    private function getVarTagMissingErrorsForStructure(array $classlike): array
    {
        $errors = [];

        foreach ($classlike['properties'] as $property) {
            $errors = array_merge($errors, $this->getVarTagMissingErrorsForProperty($classlike, $property));
        }

        foreach ($classlike['constants'] as $constant) {
            $errors = array_merge($errors, $this->getVarTagMissingErrorsForClassConstant($classlike, $constant));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     * @param array $property
     *
     * @return array
     */
    private function getVarTagMissingErrorsForProperty(array $classlike, array $property): array
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
                'message' => "Property docblock is missing @var tag.",
                'start'   => $property['startPosName'],
                'end'     => $property['endPosName']
            ]
        ];
    }

    /**
     * @param array $classlike
     * @param array $constant
     *
     * @return array
     */
    private function getVarTagMissingErrorsForClassConstant(array $classlike, array $constant): array
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
                'message' => "Constant docblock is missing @var tag.",
                'start'   => $constant['startPosName'],
                'end'     => $constant['endPosName'] + 1
            ]
        ];
    }

    /**
     * @return array
     */
    private function getParameterMissingErrors(): array
    {
        $errors = [];

        foreach ($this->outlineIndexingVisitor->getClasslikes() as $classlike) {
            $errors = array_merge($errors, $this->getParameterMissingErrorsForStructure($classlike));
        }

        foreach ($this->outlineIndexingVisitor->getGlobalFunctions() as $globalFunction) {
            $errors = array_merge($errors, $this->getParameterMissingErrorsForGlobalFunction($globalFunction));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     *
     * @return array
     */
    private function getParameterMissingErrorsForStructure(array $classlike): array
    {
        $errors = [];

        foreach ($classlike['methods'] as $method) {
            $errors = array_merge($errors, $this->getParameterMissingErrorsForMethod($classlike, $method));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     * @param array $method
     *
     * @return array
     */
    private function getParameterMissingErrorsForMethod(array $classlike, array $method): array
    {
        return $this->getParameterMissingErrorsForGlobalFunction($method);
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    private function getParameterMissingErrorsForGlobalFunction(array $globalFunction): array
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
               'message' => "Function docblock is missing @param tag for ‘{$dollarName}’.",
               'start'   => $globalFunction['startPosName'],
               'end'     => $globalFunction['endPosName']
           ];
       }

       return $issues;
    }

    /**
     * @return array
     */
    private function getParameterTypeMismatchErrors(): array
    {
        $errors = [];

        foreach ($this->outlineIndexingVisitor->getClasslikes() as $classlike) {
            $errors = array_merge($errors, $this->getParameterTypeMismatchErrorsForStructure($classlike));
        }

        foreach ($this->outlineIndexingVisitor->getGlobalFunctions() as $globalFunction) {
            $errors = array_merge($errors, $this->getParameterTypeMismatchErrorsForGlobalFunction($globalFunction));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     *
     * @return array
     */
    private function getParameterTypeMismatchErrorsForStructure(array $classlike): array
    {
        $errors = [];

        foreach ($classlike['methods'] as $method) {
            $errors = array_merge($errors, $this->getParameterTypeMismatchErrorsForMethod($classlike, $method));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     * @param array $method
     *
     * @return array
     */
    private function getParameterTypeMismatchErrorsForMethod(array $classlike, array $method): array
    {
        return $this->getParameterTypeMismatchErrorsForGlobalFunction($method);
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    private function getParameterTypeMismatchErrorsForGlobalFunction(array $globalFunction): array
    {
        if (!$globalFunction['docComment']) {
            return [];
        }

        $result = $this->docblockParser->parse(
            $globalFunction['docComment'],
            [DocblockParser::DESCRIPTION, DocblockParser::PARAM_TYPE, DocblockParser::RETURN_VALUE],
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

            $isTypeConformant = $this->parameterDocblockTypeSemanticEqualityChecker->isEqual(
                $parameter,
                $docblockParameters[$dollarName],
                $this->file,
                $globalFunction['startLine']
            );

            if ($isTypeConformant) {
                continue;
            }

            $issues[] = [
                'message' => "Function docblock has incorrect @param type for ‘{$dollarName}’.",
                'start'   => $globalFunction['startPosName'],
                'end'     => $globalFunction['endPosName']
            ];
        }

        return $issues;
    }

    /**
     * @return array
     */
    private function getReturnTypeMismatchErrors(): array
    {
        $errors = [];

        foreach ($this->outlineIndexingVisitor->getClasslikes() as $classlike) {
            $errors = array_merge($errors, $this->getReturnTypeMismatchErrorsForStructure($classlike));
        }

        foreach ($this->outlineIndexingVisitor->getGlobalFunctions() as $globalFunction) {
            $errors = array_merge($errors, $this->getReturnTypeMismatchErrorsForGlobalFunction($globalFunction));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     *
     * @return array
     */
    private function getReturnTypeMismatchErrorsForStructure(array $classlike): array
    {
        $errors = [];

        foreach ($classlike['methods'] as $method) {
            $errors = array_merge($errors, $this->getReturnTypeMismatchErrorsForMethod($classlike, $method));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     * @param array $method
     *
     * @return array
     */
    private function getReturnTypeMismatchErrorsForMethod(array $classlike, array $method): array
    {
        return $this->getReturnTypeMismatchErrorsForGlobalFunction($method);
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    private function getReturnTypeMismatchErrorsForGlobalFunction(array $globalFunction): array
    {
        if (!$globalFunction['docComment']) {
            return [];
        }

        $result = $this->docblockParser->parse(
            $globalFunction['docComment'],
            [DocblockParser::DESCRIPTION, DocblockParser::PARAM_TYPE, DocblockParser::RETURN_VALUE],
            $globalFunction['name']
        );

        if ($this->docblockAnalyzer->isFullInheritDocSyntax($result['descriptions']['short'])) {
            return [];
        }

        if (!$globalFunction['returnTypeHint'] || !$result['return']) {
            return [];
        }

        $isNullable = false;
        $type = $globalFunction['returnTypeHint'];

        if ($type !== null && mb_substr($type, 0, 1) === '?') {
            $type = mb_substr($type, 1);
            $isNullable = true;
        }

        $isTypeConformant = $this->parameterDocblockTypeSemanticEqualityChecker->isEqual(
            [
                'type'        => $type,
                'isNullable'  => $isNullable,
                'isVariadic'  => false,
                'isReference' => false
            ],
            [
                'type'        => $result['return']['type'],
                'isReference' => false
            ],
            $this->file,
            $globalFunction['startLine']
        );

        if ($isTypeConformant) {
            return [];
        }

        return [
            [
                'message' => "Function docblock @return is not equivalent to actual return type.",
                'start'   => $globalFunction['startPosName'],
                'end'     => $globalFunction['endPosName']
            ]
        ];
    }

    /**
     * @return array
     */
    private function getSuperfluousParameterErrors(): array
    {
        $errors = [];

        foreach ($this->outlineIndexingVisitor->getClasslikes() as $classlike) {
            $errors = array_merge($errors, $this->getSuperfluousParameterErrorsForStructure($classlike));
        }

        foreach ($this->outlineIndexingVisitor->getGlobalFunctions() as $globalFunction) {
            $errors = array_merge($errors, $this->getSuperfluousParameterErrorsForGlobalFunction($globalFunction));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     *
     * @return array
     */
    private function getSuperfluousParameterErrorsForStructure(array $classlike): array
    {
        $errors = [];

        foreach ($classlike['methods'] as $method) {
            $errors = array_merge($errors, $this->getSuperfluousParameterErrorsForMethod($classlike, $method));
        }

        return $errors;
    }

    /**
     * @param array $classlike
     * @param array $method
     *
     * @return array
     */
    private function getSuperfluousParameterErrorsForMethod(array $classlike, array $method): array
    {
        return $this->getSuperfluousParameterErrorsForGlobalFunction($method);
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    private function getSuperfluousParameterErrorsForGlobalFunction(array $globalFunction): array
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

        $superfluousParameterNameString = implode(', ', $superfluousParameterNames);

        return [
            [
                'message' => "Function docblock contains superfluous @param tags for: ‘{$superfluousParameterNameString}’.",
                'start'   => $globalFunction['startPosName'],
                'end'     => $globalFunction['endPosName']
            ]
        ];
    }

    /**
     * @return array
     */
    private function getDeprecatedCategoryTagWarnings(): array
    {
        $warnings = [];

        foreach ($this->outlineIndexingVisitor->getClasslikes() as $classlike) {
            $warnings = array_merge($warnings, $this->getDeprecatedCategoryTagWarningsForStructure($classlike));
        }

        return $warnings;
    }

    /**
     * @return array
     */
    private function getDeprecatedSubpackageTagWarnings(): array
    {
        $warnings = [];

        foreach ($this->outlineIndexingVisitor->getClasslikes() as $classlike) {
            $warnings = array_merge($warnings, $this->getDeprecatedSubpackageTagWarningsForStructure($classlike));
        }

        return $warnings;
    }

    /**
     * @return array
     */
    private function getDeprecatedLinkTagWarnings(): array
    {
        $warnings = [];

        foreach ($this->outlineIndexingVisitor->getClasslikes() as $classlike) {
            $warnings = array_merge($warnings, $this->getDeprecatedLinkTagWarningsForStructure($classlike));
        }

        return $warnings;
    }

    /**
     * @param array $classlike
     *
     * @return array
     */
    private function getDeprecatedCategoryTagWarningsForStructure(array $classlike): array
    {
        $result = $this->docblockParser->parse($classlike['docComment'], [
            DocblockParser::CATEGORY
        ], $classlike['name']);

        if (!$result['category']) {
            return [];
        }

        return [
            [
                'message' => "Classlike docblock contains deprecated @category tag.",
                'start'   => $classlike['startPosName'],
                'end'     => $classlike['endPosName']
            ]
        ];
    }

    /**
     * @param array $classlike
     *
     * @return array
     */
    private function getDeprecatedSubpackageTagWarningsForStructure(array $classlike): array
    {
        $result = $this->docblockParser->parse($classlike['docComment'], [
            DocblockParser::SUBPACKAGE
        ], $classlike['name']);

        if (!$result['subpackage']) {
            return [];
        }

        return [
            [
                'message' => "Classlike docblock contains deprecated @subpackage tag.",
                'start'   => $classlike['startPosName'],
                'end'     => $classlike['endPosName']
            ]
        ];
    }

    /**
     * @param array $classlike
     *
     * @return array
     *
     * @see https://github.com/phpDocumentor/fig-standards/blob/master/proposed/phpdoc.md#710-link-deprecated
     */
    private function getDeprecatedLinkTagWarningsForStructure(array $classlike): array
    {
        $result = $this->docblockParser->parse($classlike['docComment'], [
            DocblockParser::LINK
        ], $classlike['name']);

        if (!$result['link']) {
            return [];
        }

        return [
            [
                'message' => "Classlike docblock contains deprecated @link tag. Use @see instead.",
                'start'   => $classlike['startPosName'],
                'end'     => $classlike['endPosName']
            ]
        ];
    }
}
