<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\ClasslikeInfoBuilderInterface;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Visiting\OutlineFetchingVisitor;

/**
 * Analyzes code to search for missing docblocks.
 */
final class DocblockMissingAnalyzer implements AnalyzerInterface
{
    /**
     * @var OutlineFetchingVisitor
     */
    private $outlineIndexingVisitor;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @param string                        $code
     * @param TypeAnalyzer                  $typeAnalyzer
     * @param ClasslikeInfoBuilderInterface $classlikeInfoBuilder
     */
    public function __construct(
        string $code,
        TypeAnalyzer $typeAnalyzer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder
    ) {
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;

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
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getWarnings(): array
    {
        return $this->getMissingDocumentationWarnings();
    }

    /**
     * @return array
     */
    private function getMissingDocumentationWarnings(): array
    {
        $warnings = [];

        foreach ($this->outlineIndexingVisitor->getClasslikes() as $classlike) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForStructure($classlike));
        }

        foreach ($this->outlineIndexingVisitor->getGlobalFunctions() as $globalFunction) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForGlobalFunction($globalFunction));
        }

        return $warnings;
    }

    /**
     * @param array $classlike
     *
     * @return array
     */
    private function getMissingDocumentationWarningsForStructure(array $classlike): array
    {
        $warnings = [];

        $classInfo = $this->classlikeInfoBuilder->build($classlike['fqcn']);

        if ($classInfo && !$classInfo['hasDocumentation']) {
            $warnings[] = [
                'message' => "Documentation for classlike is missing.",
                'start'   => $classlike['startPosName'],
                'end'     => $classlike['endPosName']
            ];
        }

        foreach ($classlike['methods'] as $method) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForMethod($classlike, $method));
        }

        foreach ($classlike['properties'] as $property) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForProperty($classlike, $property));
        }

        foreach ($classlike['constants'] as $constant) {
            $warnings = array_merge($warnings, $this->getMissingDocumentationWarningsForClassConstant($classlike, $constant));
        }

        return $warnings;
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    private function getMissingDocumentationWarningsForGlobalFunction(array $globalFunction): array
    {
        if ($globalFunction['docComment']) {
            return [];
        }

        return [
            [
                'message' => "Documentation for function is missing.",
                'start'   => $globalFunction['startPosName'],
                'end'     => $globalFunction['endPosName']
            ]
        ];
    }

    /**
     * @param array $classlike
     * @param array $method
     *
     * @return array
     */
    private function getMissingDocumentationWarningsForMethod(array $classlike, array $method): array
    {
        if ($method['docComment']) {
            return [];
        }

        $classInfo = $this->classlikeInfoBuilder->build($classlike['fqcn']);

        if (!$classInfo ||
            !isset($classInfo['methods'][$method['name']]) ||
            $classInfo['methods'][$method['name']]['hasDocumentation']
        ) {
            return [];
        }

        return [
            [
                'message' => "Documentation for method is missing.",
                'start'   => $method['startPosName'],
                'end'     => $method['endPosName']
            ]
        ];
    }

    /**
     * @param array $classlike
     * @param array $property
     *
     * @return array
     */
    private function getMissingDocumentationWarningsForProperty(array $classlike, array $property): array
    {
        if ($property['docComment']) {
            return [];
        }

        $classInfo = $this->classlikeInfoBuilder->build($classlike['fqcn']);

        if (!$classInfo ||
            !isset($classInfo['properties'][$property['name']]) ||
            $classInfo['properties'][$property['name']]['hasDocumentation']
        ) {
            return [];
        }

        return [
            [
                'message' => "Documentation for property is missing.",
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
    private function getMissingDocumentationWarningsForClassConstant(array $classlike, array $constant): array
    {
        if ($constant['docComment']) {
            return [];
        }

        return [
            [
                'message' => "Documentation for constant is missing.",
                'start'   => $constant['startPosName'],
                'end'     => $constant['endPosName']
            ]
        ];
    }
}
