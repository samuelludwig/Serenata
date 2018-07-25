<?php

namespace Serenata\Analysis\Typing;

use UnexpectedValueException;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\DocblockTypeParser;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\NameQualificationUtilities\PositionalNameResolverInterface;
use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Utility\Typing\Type;
use Serenata\Utility\Typing\TypeList;
use Serenata\Utility\Typing\ClassType;
use Serenata\Utility\Typing\SpecialTypeString;

/**
 * Checks if a specified (normal parameter) type is semantically equal to a docblock type specification.
 */
class ParameterDocblockTypeSemanticEqualityChecker
{
    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param ClasslikeInfoBuilderInterface              $classlikeInfoBuilder
     */
    public function __construct(
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder
    ) {
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
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
        $filePosition = new FilePosition($filePath, new Position($line, 0));

        $positionalNameResolver = $this->structureAwareNameResolverFactory->create($filePosition);

        $parameterTypeList = $this->calculateParameterTypeList($parameter, $filePosition, $positionalNameResolver);
        $docblockType = $this->getResolvedDocblockParameterType($docblockParameter['type'], $filePosition, $positionalNameResolver);

        if (!$this->doesParameterTypeListMatchDocblockTypeList($parameterTypeList, $docblockType)) {
            return false;
        } elseif ($parameter['isReference'] !== $docblockParameter['isReference']) {
            return false;
        }

        return true;
    }

    /**
     * @param array                           $parameter
     * @param FilePosition                    $filePosition
     * @param PositionalNameResolverInterface $positionalNameResolver
     *
     * @return TypeList
     */
    private function calculateParameterTypeList(
        array $parameter,
        FilePosition $filePosition,
        PositionalNameResolverInterface $positionalNameResolver
    ): TypeList {
        $baseType = $positionalNameResolver->resolve($parameter['type'], $filePosition);

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
     * @param DocblockTypeParser\DocblockType  $docblockType
     * @param FilePosition                     $filePosition
     * @param PositionalNameResolverInterface  $positionalNameResolver
     *
     * @return DocblockTypeParser\DocblockType
     */
    private function getResolvedDocblockParameterType(
        DocblockTypeParser\DocblockType $docblockType,
        FilePosition $filePosition,
        PositionalNameResolverInterface $positionalNameResolver
    ): DocblockTypeParser\DocblockType {
        if ($docblockType instanceof DocblockTypeParser\CompoundDocblockType) {
            return new DocblockTypeParser\CompoundDocblockType(...array_map(function (DocblockTypeParser\DocblockType $type) use ($filePosition, $positionalNameResolver) {
                return $this->getResolvedDocblockParameterType($type, $filePosition, $positionalNameResolver);
            }, $docblockType->getParts()));
        } elseif ($docblockType instanceof DocblockTypeParser\SpecializedArrayDocblockType) {
            $resolvedType = $this->getResolvedDocblockParameterType($docblockType->getType(), $filePosition, $positionalNameResolver);

            return new DocblockTypeParser\SpecializedArrayDocblockType($resolvedType);
        } elseif ($docblockType instanceof DocblockTypeParser\ClassDocblockType) {
            $resolvedType = $positionalNameResolver->resolve($docblockType->getName(), $filePosition);

            return new DocblockTypeParser\ClassDocblockType($resolvedType);
        }

        return $docblockType;
    }

    /**
     * @param TypeList                        $parameterTypeList
     * @param DocblockTypeParser\DocblockType $docblockType
     *
     * @return bool
     */
    private function doesParameterTypeListMatchDocblockTypeList(
        TypeList $parameterTypeList,
        DocblockTypeParser\DocblockType $docblockType
    ): bool {
        if ($this->doesParameterTypeListStrictlyMatchDocblockTypeList($parameterTypeList, $docblockType)) {
            return true;
        } elseif ($parameterTypeList->hasStringType(SpecialTypeString::ARRAY_)) {
            return $this->doesParameterArrayTypeListMatchDocblockTypeList($parameterTypeList, $docblockType);
        } elseif ($this->doesParameterTypeListContainClassType($parameterTypeList)) {
            return $this->doesParameterClassTypeListMatchDocblockTypeList($parameterTypeList, $docblockType);
        }

        return false;
    }

    /**
     * @param TypeList                        $parameterTypeList
     * @param DocblockTypeParser\DocblockType $docblockType
     *
     * @return bool
     */
    private function doesParameterTypeListStrictlyMatchDocblockTypeList(
        TypeList $parameterTypeList,
        DocblockTypeParser\DocblockType $docblockType
    ): bool {
        $parameterTypeListAsCompoundTypeString = implode('|', array_map(function (Type $type) {
            return $type->toString();
        }, $parameterTypeList->toArray()));

        if ($docblockType->toString() === $parameterTypeListAsCompoundTypeString) {
            return true;
        }

        return false;
    }

    /**
     * @param TypeList $typeList
     *
     * @return bool
     */
    private function doesParameterTypeListContainClassType(TypeList $typeList): bool
    {
        return !$typeList->filter(function (Type $type) {
            return $type instanceof ClassType;
        })->isEmpty();
    }

    /**
     * @param TypeList                        $parameterTypeList
     * @param DocblockTypeParser\DocblockType $docblockType
     *
     * @return bool
     */
    private function doesParameterArrayTypeListMatchDocblockTypeList(
        TypeList $parameterTypeList,
        DocblockTypeParser\DocblockType $docblockType
    ): bool {
        $isDocblockTypeNullable =
            $docblockType instanceof DocblockTypeParser\CompoundDocblockType &&
            $docblockType->has(DocblockTypeParser\NullDocblockType::class);

        if ($parameterTypeList->hasStringType(SpecialTypeString::NULL_) !== $isDocblockTypeNullable) {
            return false;
        }

        if ($docblockType instanceof DocblockTypeParser\CompoundDocblockType) {
            $docblockTypesThatAreNotArrayTypes = $docblockType->filter(function (DocblockTypeParser\DocblockType $docblockType) {
                return
                    !$docblockType instanceof DocblockTypeParser\ArrayDocblockType &&
                    !$docblockType instanceof DocblockTypeParser\NullDocblockType;
            });

            return empty($docblockTypesThatAreNotArrayTypes);
        }

        return
            $docblockType instanceof DocblockTypeParser\ArrayDocblockType ||
            $docblockType instanceof DocblockTypeParser\NullDocblockType;
    }

    /**
     * @param TypeList                        $parameterTypeList
     * @param DocblockTypeParser\DocblockType $docblockType
     *
     * @return bool
     */
    private function doesParameterClassTypeListMatchDocblockTypeList(
        TypeList $parameterTypeList,
        DocblockTypeParser\DocblockType $docblockType
    ): bool {
        $isDocblockTypeNullable =
            $docblockType instanceof DocblockTypeParser\CompoundDocblockType &&
            $docblockType->has(DocblockTypeParser\NullDocblockType::class);

        if ($parameterTypeList->hasStringType(SpecialTypeString::NULL_) !== $isDocblockTypeNullable) {
            return false;
        }

        $docblockTypesThatAreClassTypes = null;

        if ($docblockType instanceof DocblockTypeParser\CompoundDocblockType) {
            $docblockTypesThatAreNotClassTypes = $docblockType->filter(function (DocblockTypeParser\DocblockType $docblockType) {
                return
                    !$docblockType instanceof DocblockTypeParser\ClassDocblockType &&
                    !$docblockType instanceof DocblockTypeParser\NullDocblockType;
            });

            if (!empty($docblockTypesThatAreNotClassTypes)) {
                return false;
            }

            $docblockTypesThatAreClassTypes = $docblockType->filter(function (DocblockTypeParser\DocblockType $docblockType) {
                return $docblockType instanceof DocblockTypeParser\ClassDocblockType;
            });
        } elseif (!$docblockType instanceof DocblockTypeParser\ClassDocblockType) {
            return false;
        } else {
            $docblockTypesThatAreClassTypes = [$docblockType];
        }

        $parameterClassTypes = $parameterTypeList->filter(function (Type $type) {
            return $type instanceof ClassType;
        });

        $parameterClassType = $parameterClassTypes->toArray()[0];

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
     * @param DocblockTypeParser\ClassDocblockType $docblockType
     * @param ClassType                            $type
     *
     * @return bool
     */
    private function doesDocblockClassSatisfyTypeParameterClassType(
        DocblockTypeParser\ClassDocblockType $docblockType,
        ClassType $type
    ): bool {
        if ($docblockType->getName() === $type->toString()) {
            return true;
        }

        try {
            $typeClassInfo = $this->classlikeInfoBuilder->build($type->toString());
            $docblockTypeClassInfo = $this->classlikeInfoBuilder->build($docblockType->getName());
        } catch (UnexpectedValueException $e) {
            return false;
        }

        if (in_array($typeClassInfo['fqcn'], $docblockTypeClassInfo['parents'], true)) {
            return true;
        } elseif (in_array($typeClassInfo['fqcn'], $docblockTypeClassInfo['interfaces'], true)) {
            return true;
        }

        return false;
    }
}
