<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverInterface;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

/**
 * Checks if a specified (normal parameter) type is semantically equal to a docblock type specification.
 */
class ParameterDocblockTypeSemanticEqualityChecker
{
    /**
     * @var FileTypeResolverFactoryInterface
     */
    private $fileTypeResolverFactory;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param TypeAnalyzer                     $typeAnalyzer
     */
    public function __construct(FileTypeResolverFactoryInterface $fileTypeResolverFactory, TypeAnalyzer $typeAnalyzer)
    {
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->typeAnalyzer = $typeAnalyzer;
    }

    /**
     * @param array  $parameter
     * @param array  $docblockParameter
     * @param string $filePath
     * @param int    $line
     *
     * @return bool
     */
    public function isEqual(array $parameter, array $docblockParameter, string $filePath, int $line): bool
    {
        $fileTypeResolver = $this->fileTypeResolverFactory->create($filePath);

        $parameterTypeList = $this->calculateParameterTypeList($parameter, $line, $fileTypeResolver);
        $docblockTypeList = $this->calculateDocblockParameterTypeList($docblockParameter, $line, $fileTypeResolver);

        if (!$this->doesParameterTypeListMatchDocblockTypeList($parameterTypeList, $docblockTypeList)) {
            return false;
        } elseif ($parameter['isReference'] !== $docblockParameter['isReference']) {
            return false;
        }

        return true;
    }

    /**
     * @param array                     $parameter
     * @param int                       $line
     * @param FileTypeResolverInterface $fileTypeResolver
     *
     * @return array
     */
    protected function calculateParameterTypeList(
        array $parameter,
        int $line,
        FileTypeResolverInterface $fileTypeResolver
    ): array {
        $baseType = $fileTypeResolver->resolve($parameter['type'], $line);

        if ($parameter['isVariadic']) {
            $baseType .= '[]';
        }

        $typeList = [$baseType];

        if ($parameter['isNullable']) {
            $typeList[] = 'null';
        }

        return $typeList;
    }

    /**
     * @param array                     $docblockParameter
     * @param int                       $line
     * @param FileTypeResolverInterface $fileTypeResolver
     *
     * @return array
     */
    protected function calculateDocblockParameterTypeList(
        array $docblockParameter,
        int $line,
        FileTypeResolverInterface $fileTypeResolver
    ): array {
        $typeList = [];

        foreach ($this->typeAnalyzer->getTypesForTypeSpecification($docblockParameter['type']) as $docblockType) {
            if ($this->typeAnalyzer->isArraySyntaxTypeHint($docblockType)) {
                $valueType = $this->typeAnalyzer->getValueTypeFromArraySyntaxTypeHint($docblockType);
            } else {
                $valueType = $docblockType;
            }

            $resolvedValueType = $fileTypeResolver->resolve($valueType, $line);

            if ($this->typeAnalyzer->isArraySyntaxTypeHint($docblockType)) {
                $resolvedValueType .= '[]';
            }

            $typeList[] = $resolvedValueType;
        }

        return $typeList;
    }

    /**
     * @param string[] $parameterTypeList
     * @param string[] $docblockTypeList
     *
     * @return bool
     */
    protected function doesParameterTypeListMatchDocblockTypeList(
        array $parameterTypeList,
        array $docblockTypeList
    ): bool {
        if (count(array_intersect($docblockTypeList, $parameterTypeList)) === count($parameterTypeList)) {
            return true;
        } elseif (!in_array('array', $parameterTypeList, true)) {
            return false;
        }

        $docblockTypesThatAreNotArrayTypes = array_filter($docblockTypeList, function ($docblockType) {
            return !$this->typeAnalyzer->isArraySyntaxTypeHint($docblockType);
        });

        $docblockTypesThatAreNotArrayTypes = array_values($docblockTypesThatAreNotArrayTypes);

        if (!empty($docblockTypesThatAreNotArrayTypes) && $docblockTypesThatAreNotArrayTypes) {
            foreach ($docblockTypesThatAreNotArrayTypes as $docblockTypesThatIsNotArrayType) {
                if ($docblockTypesThatIsNotArrayType !== 'null') {
                    return false;
                }
            }
        }

        return true;
    }
}
