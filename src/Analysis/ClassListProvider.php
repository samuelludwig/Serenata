<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;
use PhpIntegrator\Analysis\ClasslikeInfoBuilderProviderInterface;

use PhpIntegrator\Analysis\Conversion\MethodConverter;
use PhpIntegrator\Analysis\Conversion\ConstantConverter;
use PhpIntegrator\Analysis\Conversion\PropertyConverter;
use PhpIntegrator\Analysis\Conversion\FunctionConverter;
use PhpIntegrator\Analysis\Conversion\ClasslikeConverter;
use PhpIntegrator\Analysis\Conversion\ClasslikeConstantConverter;

use PhpIntegrator\Analysis\Relations\TraitUsageResolver;
use PhpIntegrator\Analysis\Relations\InheritanceResolver;
use PhpIntegrator\Analysis\Relations\InterfaceImplementationResolver;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\FileClassListProviderInterface;

use PhpIntegrator\Indexing\IndexDatabase;

use PhpIntegrator\UserInterface\ClasslikeInfoBuilderWhiteHolingProxyProvider;

/**
 * Retrieves a list of available classes.
 */
class ClassListProvider implements FileClassListProviderInterface
{
    /**
     * @var IndexDatabase
     */
    protected $indexDatabase;

    /**
     * @var ClasslikeInfoBuilderWhiteHolingProxyProvider
     */
    protected $storageProxy;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @param ConstantConverter                     $constantConverter
     * @param ClasslikeConstantConverter            $classlikeConstantConverter
     * @param PropertyConverter                     $propertyConverter
     * @param FunctionConverter                     $functionConverter
     * @param MethodConverter                       $methodConverter
     * @param ClasslikeConverter                    $classlikeConverter
     * @param InheritanceResolver                   $inheritanceResolver
     * @param InterfaceImplementationResolver       $interfaceImplementationResolver
     * @param TraitUsageResolver                    $traitUsageResolver
     * @param ClasslikeInfoBuilderProviderInterface $classlikeInfoBuilderProvider
     * @param TypeAnalyzer                          $typeAnalyzer
     * @param IndexDatabase                         $indexDatabase
     */
    public function __construct(
        ConstantConverter $constantConverter,
        ClasslikeConstantConverter $classlikeConstantConverter,
        PropertyConverter $propertyConverter,
        FunctionConverter $functionConverter,
        MethodConverter $methodConverter,
        ClasslikeConverter $classlikeConverter,
        InheritanceResolver $inheritanceResolver,
        InterfaceImplementationResolver $interfaceImplementationResolver,
        TraitUsageResolver $traitUsageResolver,
        ClasslikeInfoBuilderProviderInterface $classlikeInfoBuilderProvider,
        TypeAnalyzer $typeAnalyzer,
        IndexDatabase $indexDatabase
    ) {
        $this->indexDatabase = $indexDatabase;
        $this->storageProxy = new ClasslikeInfoBuilderWhiteHolingProxyProvider($classlikeInfoBuilderProvider);

        $this->classlikeInfoBuilder = new ClasslikeInfoBuilder(
            $constantConverter,
            $classlikeConstantConverter,
            $propertyConverter,
            $functionConverter,
            $methodConverter,
            $classlikeConverter,
            $inheritanceResolver,
            $interfaceImplementationResolver,
            $traitUsageResolver,
            $this->storageProxy,
            $typeAnalyzer
        );
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->getAllForOptionalFile(null);
    }

    /**
     * @param string $file
     *
     * @return array
     */
    public function getAllForFile(string $file): array
    {
        return $this->getAllForOptionalFile($file);
    }

    /**
     * @param ?string $file
     *
     * @return array
     */
    protected function getAllForOptionalFile(?string $file): array
    {
        $result = [];

        foreach ($this->indexDatabase->getAllStructuresRawInfo($file) as $element) {
            $result[$element['fqcn']] = $this->getClassInfoFromRawData($element);
        }

        return $result;
    }

    /**
     * @param array $element
     *
     * @return array
     */
    protected function getClassInfoFromRawData(array $element): array
    {
        // Directly load in the raw information we already have, this avoids performing a database query for each
        // record.
        $this->storageProxy->setStructureRawInfo($element);

        $info = $this->classlikeInfoBuilder->getClasslikeInfo($element['name']);

        unset($info['constants'], $info['properties'], $info['methods']);

        return $info;
    }
}
