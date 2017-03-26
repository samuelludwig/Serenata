<?php

namespace PhpIntegrator\Analysis\Typing;

use UnexpectedValueException;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverInterface;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

use PhpIntegrator\Utility\DocblockTyping\DocblockType;
use PhpIntegrator\Utility\DocblockTyping\DocblockTypeList;
use PhpIntegrator\Utility\DocblockTyping\ClassDocblockType;
use PhpIntegrator\Utility\DocblockTyping\ArrayDocblockType;
use PhpIntegrator\Utility\DocblockTyping\SpecialDocblockTypeString;

use PhpIntegrator\Utility\Typing\Type;
use PhpIntegrator\Utility\Typing\TypeList;
use PhpIntegrator\Utility\Typing\ClassType;
use PhpIntegrator\Utility\Typing\SpecialTypeString;

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
     * @var ClasslikeInfoBuilder
     */
    private $classlikeInfoBuilder;

    /**
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param ClasslikeInfoBuilder             $classlikeInfoBuilder
     */
    public function __construct(
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        ClasslikeInfoBuilder $classlikeInfoBuilder
    ) {
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
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
            $typeList[] = SpecialTypeString::NULL_;
        }

        return TypeList::createFromStringTypeList(...$typeList);
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

        $docblockTypeList = DocblockTypeList::createFromDocblockTypeSpecification($docblockParameter['type']);

        /** @var DocblockType $docblockType */
        foreach ($docblockTypeList as $docblockType) {
            if ($docblockType instanceof ArrayDocblockType) {
                $valueType = $docblockType->getValueTypeFromArrayType();
            } else {
                $valueType = $docblockType;
            }

            if ($valueType instanceof ClassDocblockType) {
                $resolvedValueType = $fileTypeResolver->resolve($valueType, $line);
            } else {
                $resolvedValueType = $valueType;
            }

            if ($docblockType instanceof ArrayDocblockType) {
                $resolvedValueType .= '[]';
            }

            $typeList[] = $resolvedValueType;
        }

        return DocblockTypeList::createFromStringTypeList(...$typeList);
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
        if ($docblockTypeList->equals(DocblockTypeList::createFromTypeList($parameterTypeList))) {
            return true;
        } elseif ($parameterTypeList->hasStringType(SpecialTypeString::ARRAY_)) {
            return $this->doesParameterArrayTypeListMatchDocblockTypeList($parameterTypeList, $docblockTypeList);
        } elseif ($parameterTypeList->hasStringType(SpecialTypeString::ARRAY_)) {
            return $this->doesParameterArrayTypeListMatchDocblockTypeList($parameterTypeList, $docblockTypeList);
        } elseif ($this->doesParameterTypeListContainClassType($parameterTypeList)) {
            return $this->doesParameterClassTypeListMatchDocblockTypeList($parameterTypeList, $docblockTypeList);
        }

        return false;
    }

    /**
     * @param TypeList $typeList
     *
     * @return bool
     */
    protected function doesParameterTypeListContainClassType(TypeList $typeList): bool
    {
        return !empty(array_filter($typeList->toArray(), function (Type $type) {
            return $type instanceof ClassType;
        }));
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
        if ($parameterTypeList->hasStringType(SpecialTypeString::NULL_) !==
            $docblockTypeList->hasStringType(SpecialDocblockTypeString::NULL_)
        ) {
            return false;
        }

        $docblockTypesThatAreNotArrayTypes = array_filter($docblockTypeList->toArray(), function (DocblockType $docblockType) {
            return (!$docblockType instanceof ArrayDocblockType && $docblockType->toString() !== SpecialDocblockTypeString::NULL_);
        });

        return empty($docblockTypesThatAreNotArrayTypes);
    }

    /**
     * @param TypeList         $parameterTypeList
     * @param DocblockTypeList $docblockTypeList
     *
     * @return bool
     */
    protected function doesParameterClassTypeListMatchDocblockTypeList(
        TypeList $parameterTypeList,
        DocblockTypeList $docblockTypeList
    ): bool {
        if ($parameterTypeList->hasStringType(SpecialTypeString::NULL_) !==
            $docblockTypeList->hasStringType(SpecialDocblockTypeString::NULL_)
        ) {
            return false;
        }

        $docblockTypesThatAreNotClassTypes = array_filter($docblockTypeList->toArray(), function (DocblockType $docblockType) {
            return (!$docblockType instanceof ClassDocblockType && $docblockType->toString() !== SpecialDocblockTypeString::NULL_);
        });

        if (!empty($docblockTypesThatAreNotClassTypes)) {
            return false;
        }

        $docblockTypesThatAreClassTypes = array_filter($docblockTypeList->toArray(), function (DocblockType $docblockType) {
            return $docblockType instanceof ClassDocblockType;
        });

        $parameterClassTypes = array_filter($parameterTypeList->toArray(), function (Type $type) {
            return $type instanceof ClassType;
        });

        $parameterClassType = array_shift($parameterClassTypes);

        foreach ($docblockTypesThatAreClassTypes as $docblockTypeThatIsClassType) {
            if (!$this->doesDocblockClassSatisfyTypeParameterClassType($docblockTypeThatIsClassType, $parameterClassType)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Indicates if the docblock satisfies the parameter type (hint).
     *
     * Satisfaction is achieved if either the parameter type matches the docblock type or if the docblock type
     * specializes the parameter type (i.e. it is a subclass of it or implements it as interface).
     *
     * @param ClassDocblockType $docblockType
     * @param ClassType         $type
     *
     * @return bool
     */
    protected function doesDocblockClassSatisfyTypeParameterClassType(
        ClassDocblockType $docblockType,
        ClassType $type
    ): bool {
        if ($docblockType->toString() === $type->toString()) {
            return true;
        }

        try {
            $typeClassInfo = $this->classlikeInfoBuilder->getClasslikeInfo($type->toString());
            $docblockTypeClassInfo = $this->classlikeInfoBuilder->getClasslikeInfo($docblockType->toString());
        } catch (UnexpectedValueException $e) {
            return false;
        }

        if (in_array($typeClassInfo['name'], $docblockTypeClassInfo['parents'], true)) {
            return true;
        } elseif (in_array($typeClassInfo['name'], $docblockTypeClassInfo['interfaces'], true)) {
            return true;
        }

        return false;
    }
}
