<?php

namespace PhpIntegrator\Analysis\Typing;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\SpecialDocblockType;

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
    ): TypeList {
        $baseType = $fileTypeResolver->resolve($parameter['type'], $line);

        if ($parameter['isVariadic']) {
            $baseType .= '[]';
        }

        $typeList = [$baseType];

        if ($parameter['isNullable']) {
            $typeList[] = SpecialDocblockType::NULL_;
        }

        return new TypeList(...$typeList);
    }

    /**
     * @param array                     $docblockParameter
     * @param int                       $line
     * @param FileTypeResolverInterface $fileTypeResolver
     *
     * @return DocblockTypeList
     */
    protected function calculateDocblockParameterTypeList(
        array $docblockParameter,
        int $line,
        FileTypeResolverInterface $fileTypeResolver
    ): DocblockTypeList {
        $typeList = [];

        foreach ($this->typeAnalyzer->getTypesForTypeSpecification($docblockParameter['type']) as $docblockType) {
            if ($this->typeAnalyzer->isArraySyntaxTypeHint($docblockType)) {
                $valueType = $this->typeAnalyzer->getValueTypeFromArraySyntaxTypeHint($docblockType);
            } else {
                $valueType = $docblockType;
            }

            if ($this->typeAnalyzer->isClassType($valueType)) {
                $resolvedValueType = $fileTypeResolver->resolve($valueType, $line);
            } else {
                $resolvedValueType = $valueType;
            }

            if ($this->typeAnalyzer->isArraySyntaxTypeHint($docblockType)) {
                $resolvedValueType .= '[]';
            }

            $typeList[] = $resolvedValueType;
        }

        return new DocblockTypeList(...$typeList);
    }

    /**
     * @param TypeList         $parameterTypeList
     * @param DocblockTypeList $docblockTypeList
     *
     * @return bool
     */
    protected function doesParameterTypeListMatchDocblockTypeList(
        TypeList $parameterTypeList,
        DocblockTypeList $docblockTypeList
    ): bool {
        if ($docblockTypeList->equals($parameterTypeList)) {
            return true;
        } elseif ($parameterTypeList->has(SpecialDocblockType::ARRAY_)) {
            return $this->doesParameterArrayTypeListMatchDocblockTypeList($parameterTypeList, $docblockTypeList);
        }

        return false;
    }

    /**
     * @param TypeList         $parameterTypeList
     * @param DocblockTypeList $docblockTypeList
     *
     * @return bool
     */
    protected function doesParameterArrayTypeListMatchDocblockTypeList(
        TypeList $parameterTypeList,
        DocblockTypeList $docblockTypeList
    ): bool {
        $docblockTypesThatAreNotArrayTypes = array_filter($docblockTypeList->toArray(), function ($docblockType) {
            return !$this->typeAnalyzer->isArraySyntaxTypeHint($docblockType);
        });

        $docblockTypesThatAreNotArrayTypes = array_values($docblockTypesThatAreNotArrayTypes);

        if (!empty($docblockTypesThatAreNotArrayTypes) && $docblockTypesThatAreNotArrayTypes) {
            foreach ($docblockTypesThatAreNotArrayTypes as $docblockTypesThatIsNotArrayType) {
                if ($docblockTypesThatIsNotArrayType === SpecialDocblockType::NULL_) {
                    if (!$parameterTypeList->has(SpecialDocblockType::NULL_)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }

        if ($parameterTypeList->has(SpecialDocblockType::NULL_) && !$docblockTypeList->has(SpecialDocblockType::NULL_)) {
            return false;
        }

        return true;
    }
}
