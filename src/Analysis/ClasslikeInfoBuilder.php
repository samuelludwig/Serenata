<?php

namespace Serenata\Analysis;

use ArrayObject;
use UnexpectedValueException;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

/**
 * Builds a complete structure of data for a classlike, including children and members.
 *
 * @final
 */
/*final */class ClasslikeInfoBuilder implements ClasslikeInfoBuilderInterface
{
    /**
     * @var Conversion\ClasslikeConstantConverter
     */
    private $classlikeConstantConverter;

    /**
     * @var Conversion\PropertyConverter
     */
    private $propertyConverter;

    /**
     * @var Conversion\MethodConverter
     */
    private $methodConverter;

    /**
     * @var Conversion\ClasslikeConverter
     */
    private $classlikeConverter;

    /**
     * @var Relations\InheritanceResolver
     */
    private $inheritanceResolver;

    /**
     * @var Relations\InterfaceImplementationResolver
     */
    private $interfaceImplementationResolver;

    /**
     * @var Relations\TraitUsageResolver
     */
    private $traitUsageResolver;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var Typing\TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var string[]
     */
    private $resolutionStack = [];

    /**
     * @param Conversion\ClasslikeConstantConverter     $classlikeConstantConverter
     * @param Conversion\PropertyConverter              $propertyConverter
     * @param Conversion\MethodConverter                $methodConverter
     * @param Conversion\ClasslikeConverter             $classlikeConverter
     * @param Relations\InheritanceResolver             $inheritanceResolver
     * @param Relations\InterfaceImplementationResolver $interfaceImplementationResolver
     * @param Relations\TraitUsageResolver              $traitUsageResolver
     * @param StorageInterface                          $storage
     * @param Typing\TypeAnalyzer                       $typeAnalyzer
     */
    public function __construct(
        Conversion\ClasslikeConstantConverter $classlikeConstantConverter,
        Conversion\PropertyConverter $propertyConverter,
        Conversion\MethodConverter $methodConverter,
        Conversion\ClasslikeConverter $classlikeConverter,
        Relations\InheritanceResolver $inheritanceResolver,
        Relations\InterfaceImplementationResolver $interfaceImplementationResolver,
        Relations\TraitUsageResolver $traitUsageResolver,
        StorageInterface $storage,
        Typing\TypeAnalyzer $typeAnalyzer
    ) {
        $this->classlikeConstantConverter = $classlikeConstantConverter;
        $this->propertyConverter = $propertyConverter;
        $this->methodConverter = $methodConverter;
        $this->classlikeConverter = $classlikeConverter;

        $this->inheritanceResolver = $inheritanceResolver;
        $this->interfaceImplementationResolver = $interfaceImplementationResolver;
        $this->traitUsageResolver = $traitUsageResolver;

        $this->storage = $storage;
        $this->typeAnalyzer = $typeAnalyzer;
    }

    /// @inherited
    public function build(string $fqcn): array
    {
        $this->resolutionStack = [];

        return $this->getCheckedClasslikeInfo($fqcn, '')->getArrayCopy();
    }

    /**
     * @param string $fqcn
     * @param string $originFqcn
     *
     * @throws CircularDependencyException
     *
     * @return ArrayObject
     */
    private function getCheckedClasslikeInfo(string $fqcn, string $originFqcn): ArrayObject
    {
        if (in_array($fqcn, $this->resolutionStack, true)) {
            throw new CircularDependencyException("Circular dependency detected from {$originFqcn} to {$fqcn}!");
        }

        $this->resolutionStack[] = $fqcn;

        $data = $this->getUncheckedClasslikeInfo($fqcn);

        array_pop($this->resolutionStack);

        return $data;
    }

    /**
     * @param string $fqcn
     *
     * @throws UnexpectedValueException
     *
     * @return ArrayObject
     */
    private function getUncheckedClasslikeInfo(string $fqcn): ArrayObject
    {
        $classlike = $this->storage->findStructureByFqcn($fqcn);

        if ($classlike === null) {
            throw new UnexpectedValueException('The structural element "' . $fqcn . '" was not found!');
        }

        return $this->fetchFlatClasslikeInfo($classlike);
    }

    /**
     * Builds information about a classlike in a flat structure, meaning it doesn't resolve any inheritance or interface
     * implementations. Instead, it will only list members and data directly relevant to the classlike.
     *
     * @param Structures\Classlike $classlike
     *
     * @return ArrayObject
     */
    private function fetchFlatClasslikeInfo(Structures\Classlike $classlike): ArrayObject
    {
        $classlikeInfo = new ArrayObject($this->classlikeConverter->convert($classlike) + [
            'parents'            => [],
            'interfaces'         => [],
            'traits'             => [],

            'directParents'      => [],
            'directInterfaces'   => [],
            'directTraits'       => [],
            'directChildren'     => [],
            'directImplementors' => [],
            'directTraitUsers'   => [],

            'constants'          => [],
            'properties'         => [],
            'methods'            => [],
        ]);

        $this->buildDirectChildrenInfo($classlikeInfo, $classlike);
        $this->buildDirectImplementorsInfo($classlikeInfo, $classlike);
        $this->buildTraitUsersInfo($classlikeInfo, $classlike);
        $this->buildConstantsInfo($classlikeInfo, $classlike);
        $this->buildPropertiesInfo($classlikeInfo, $classlike);
        $this->buildMethodsInfo($classlikeInfo, $classlike);
        $this->buildTraitsInfo($classlikeInfo, $classlike);

        $this->resolveNormalTypes($classlikeInfo);
        $this->resolveSelfTypesTo($classlikeInfo, $classlikeInfo['fqcn']);

        $this->buildParentsInfo($classlikeInfo, $classlike);
        $this->buildInterfacesInfo($classlikeInfo, $classlike);

        $this->resolveStaticTypesTo($classlikeInfo, $classlikeInfo['fqcn']);

        return $classlikeInfo;
    }

    /**
     * @param ArrayObject          $classlikeInfo
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function buildDirectChildrenInfo(ArrayObject $classlikeInfo, Structures\Classlike $classlike): void
    {
        if (!$classlike instanceof Structures\Class_ && !$classlike instanceof Structures\Interface_) {
            return;
        }

        foreach ($classlike->getChildFqcns() as $childFqcn) {
            $classlikeInfo['directChildren'][] = $childFqcn;
        }
    }

    /**
     * @param ArrayObject          $classlikeInfo
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function buildDirectImplementorsInfo(ArrayObject $classlikeInfo, Structures\Classlike $classlike): void
    {
        if (!$classlike instanceof Structures\Interface_) {
            return;
        }

        foreach ($classlike->getImplementorFqcns() as $implementorFqcn) {
            $classlikeInfo['directImplementors'][] = $implementorFqcn;
        }
    }

    /**
     * @param ArrayObject          $classlikeInfo
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function buildTraitUsersInfo(ArrayObject $classlikeInfo, Structures\Classlike $classlike): void
    {
        if (!$classlike instanceof Structures\Trait_) {
            return;
        }

        foreach ($classlike->getTraitUserFqcns() as $traitUserFqcn) {
            $classlikeInfo['directTraitUsers'][] = $traitUserFqcn;
        }
    }

    /**
     * @param ArrayObject          $classlikeInfo
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function buildConstantsInfo(ArrayObject $classlikeInfo, Structures\Classlike $classlike): void
    {
        foreach ($classlike->getConstants() as $constant) {
            $classlikeInfo['constants'][$constant->getName()] = $this->classlikeConstantConverter->convertForClass(
                $constant,
                $classlikeInfo
            );
        }
    }

    /**
     * @param ArrayObject          $classlikeInfo
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function buildPropertiesInfo(ArrayObject $classlikeInfo, Structures\Classlike $classlike): void
    {
        foreach ($classlike->getProperties() as $property) {
            $classlikeInfo['properties'][$property->getName()] = $this->propertyConverter->convertForClass(
                $property,
                $classlikeInfo
            );
        }
    }

    /**
     * @param ArrayObject          $classlikeInfo
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function buildMethodsInfo(ArrayObject $classlikeInfo, Structures\Classlike $classlike): void
    {
        foreach ($classlike->getMethods() as $method) {
            $classlikeInfo['methods'][$method->getName()] = $this->methodConverter->convertForClass(
                $method,
                $classlikeInfo
            );
        }
    }

    /**
     * @param ArrayObject         $classlikeInfo
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function buildTraitsInfo(ArrayObject $classlikeInfo, Structures\Classlike $classlike): void
    {
        if (!$classlike instanceof Structures\Class_ && !$classlike instanceof Structures\Trait_) {
            return;
        }

        foreach ($classlike->getTraitFqcns() as $traitFqcn) {
            $classlikeInfo['traits'][] = $traitFqcn;
            $classlikeInfo['directTraits'][] = $traitFqcn;

            try {
                $traitInfo = $this->getCheckedClasslikeInfo($traitFqcn, $classlikeInfo['fqcn']);
            } catch (UnexpectedValueException|CircularDependencyException $e) {
                continue;
            }

            $this->traitUsageResolver->resolveUseOf(
                $traitInfo,
                $classlikeInfo,
                $classlike->getTraitAliases(),
                $classlike->getTraitPrecedences()
            );
        }
    }

    /**
     * @param ArrayObject          $classlikeInfo
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function buildParentsInfo(ArrayObject $classlikeInfo, Structures\Classlike $classlike): void
    {
        $parentFqcns = [];

        if (!$classlike instanceof Structures\Class_ && !$classlike instanceof Structures\Interface_) {
            return;
        } elseif ($classlike instanceof Structures\Class_) {
            $parentFqcns = array_filter([$classlike->getParentFqcn()]);
        } else {
            $parentFqcns = $classlike->getParentFqcns();
        }

        foreach ($parentFqcns as $parentFqcn) {
            $classlikeInfo['parents'][] = $parentFqcn;
            $classlikeInfo['directParents'][] = $parentFqcn;

            try {
                $parentInfo = $this->getCheckedClasslikeInfo($parentFqcn, $classlikeInfo['fqcn']);
            } catch (UnexpectedValueException|CircularDependencyException $e) {
                continue;
            }

            $this->inheritanceResolver->resolveInheritanceOf($parentInfo, $classlikeInfo);
        }
    }

    /**
     * @param ArrayObject          $classlikeInfo
     * @param Structures\Classlike $classlike
     *
     * @return void
     */
    private function buildInterfacesInfo(ArrayObject $classlikeInfo, Structures\Classlike $classlike): void
    {
        if (!$classlike instanceof Structures\Class_) {
            return;
        }

        foreach ($classlike->getInterfaceFqcns() as $interfaceFqcn) {
            $classlikeInfo['interfaces'][] = $interfaceFqcn;
            $classlikeInfo['directInterfaces'][] = $interfaceFqcn;

            try {
                $interfaceInfo = $this->getCheckedClasslikeInfo($interfaceFqcn, $classlikeInfo['fqcn']);
            } catch (UnexpectedValueException|CircularDependencyException $e) {
                continue;
            }

            $this->interfaceImplementationResolver->resolveImplementationOf($interfaceInfo, $classlikeInfo);
        }
    }

    /**
     * @param ArrayObject $result
     * @param string      $elementFqcn
     *
     * @return void
     */
    private function resolveSelfTypesTo(ArrayObject $result, $elementFqcn): void
    {
        $typeAnalyzer = $this->typeAnalyzer;

        $this->walkTypes($result, function (array &$type) use ($elementFqcn, $typeAnalyzer): void {
            if ($type['resolvedType'] !== null) {
                $type['resolvedType'] = $typeAnalyzer->interchangeSelfWithActualType(
                    $type['resolvedType'],
                    $elementFqcn
                );
            }
        });
    }

    /**
     * @param ArrayObject $result
     * @param string      $elementFqcn
     *
     * @return void
     */
    private function resolveStaticTypesTo(ArrayObject $result, $elementFqcn): void
    {
        $typeAnalyzer = $this->typeAnalyzer;

        $this->walkTypes($result, function (array &$type) use ($elementFqcn, $typeAnalyzer): void {
            $replacedThingy = $typeAnalyzer->interchangeStaticWithActualType($type['type'], $elementFqcn);
            $replacedThingy = $typeAnalyzer->interchangeThisWithActualType($replacedThingy, $elementFqcn);

            if ($type['type'] !== $replacedThingy) {
                $type['resolvedType'] = $replacedThingy;
            }
        });
    }

    /**
     * @param ArrayObject $result
     *
     * @return void
     */
    private function resolveNormalTypes(ArrayObject $result): void
    {
        $typeAnalyzer = $this->typeAnalyzer;

        $this->walkTypes($result, function (array &$type) use ($typeAnalyzer): void {
            if ($type['type'] !== null && $typeAnalyzer->isClassType($type['type'])) {
                $type['resolvedType'] = $typeAnalyzer->getNormalizedFqcn($type['type']);
            } else {
                $type['resolvedType'] = $type['type'];
            }
        });
    }

    /**
     * @param ArrayObject $result
     * @param callable    $callable
     *
     * @return void
     */
    private function walkTypes(ArrayObject $result, callable $callable): void
    {
        foreach ($result['methods'] as $name => &$method) {
            foreach ($method['parameters'] as &$parameter) {
                foreach ($parameter['types'] as &$type) {
                    $callable($type);
                }
            }

            foreach ($method['returnTypes'] as &$returnType) {
                $callable($returnType);
            }
        }

        foreach ($result['properties'] as $name => &$property) {
            foreach ($property['types'] as &$type) {
                $callable($type);
            }
        }

        foreach ($result['constants'] as $name => &$constant) {
            foreach ($constant['types'] as &$type) {
                $callable($type);
            }
        }
    }
}
