<?php

namespace Serenata\Symbols;

use DomainException;

use Serenata\Utility\Location;

use Serenata\Indexing\Structures\File;
use Serenata\Indexing\Structures\Class_;
use Serenata\Indexing\Structures\Method;
use Serenata\Indexing\Structures\Trait_;
use Serenata\Indexing\Structures\Constant;
use Serenata\Indexing\Structures\Property;
use Serenata\Indexing\Structures\Classlike;
use Serenata\Indexing\Structures\Function_;
use Serenata\Indexing\Structures\Interface_;
use Serenata\Indexing\Structures\ConstantLike;
use Serenata\Indexing\Structures\FunctionLike;
use Serenata\Indexing\Structures\ClassConstant;

/**
 * Retrieves a list of symbols for a document.
 */
final class DocumentSymbolRetriever
{
    /**
     * @param File $file
     *
     * @return SymbolInformation[]|null
     */
    public function retrieve(File $file): ?array
    {
        return $this->sortSymbolListByLocation(array_merge(
            $this->getConstantSymbolsForFile($file),
            $this->getFunctionSymbolsForFile($file),
            $this->getClasslikeSymbolsForFile($file)
        ));
    }

    /**
     * @param File $file
     *
     * @return array
     */
    private function getConstantSymbolsForFile(File $file): array
    {
        return array_map(function (Constant $constant) use ($file): SymbolInformation {
            return $this->createSymbolForConstantLike($constant, $file);
        }, $file->getConstants());
    }

    /**
     * @param File $file
     *
     * @return array
     */
    private function getFunctionSymbolsForFile(File $file): array
    {
        return array_map(function (Function_ $function) use ($file): SymbolInformation {
            return $this->createSymbolForFunctionLike($function, $file);
        }, $file->getFunctions());
    }

    /**
     * @param File $file
     *
     * @return array
     */
    private function getClasslikeSymbolsForFile(File $file): array
    {
        $symbolLists = array_map(function (Classlike $classlike) use ($file): array {
            return $this->getMemberSymbolsForClasslike($classlike, $file);
        }, $file->getClasslikes());

        return array_reduce($symbolLists, function (array $finalSymbolList, array $symbolList) {
            return array_merge($finalSymbolList, $symbolList);
        }, []);
    }

    /**
     * @param Classlike $classlike
     * @param File      $file
     *
     * @return array
     */
    private function getMemberSymbolsForClasslike(Classlike $classlike, File $file): array
    {
        $constants = array_map(function (ClassConstant $classConstant) use ($file, $classlike): ?SymbolInformation {
            if ($classConstant->getName() === 'class') {
                return null;
            }

            return $this->createSymbolForConstantLike($classConstant, $file, $classlike->getName());
        }, $classlike->getConstants());

        $constants = array_filter($constants);

        $methods = array_map(function (Method $method) use ($file, $classlike): SymbolInformation {
            return $this->createSymbolForFunctionLike($method, $file, $classlike->getName());
        }, $classlike->getMethods());

        $properties = array_map(function (Property $property) use ($file, $classlike): SymbolInformation {
            return $this->createSymbolForProperty($property, $file, $classlike->getName());
        }, $classlike->getProperties());

        return array_merge(
            [$this->createSymbolForClasslike($classlike, $file)],
            $constants,
            $methods,
            $properties
        );
    }

    /**
     * @param Classlike $classlike
     * @param File      $file
     *
     * @return SymbolInformation
     */
    private function createSymbolForClasslike(Classlike $classlike, File $file): SymbolInformation
    {
        if ($classlike instanceof Class_) {
            $kind = SymbolKind::CLASS_;
        } elseif ($classlike instanceof Interface_) {
            $kind = SymbolKind::INTERFACE_;
        } elseif ($classlike instanceof Trait_) {
            $kind = SymbolKind::CLASS_; // Due to lack of a better kind.
        } else {
            throw new DomainException('Unknown classlike class "' . get_class($classlike) . '" encountered');
        }

        return new SymbolInformation(
            $classlike->getName(),
            $kind,
            $classlike->getIsDeprecated(),
            new Location($file->getUri(), $classlike->getRange()),
            null
        );
    }

    /**
     * @param ConstantLike $constant
     * @param File         $file
     * @param string|null  $containerName
     *
     * @return SymbolInformation
     */
    private function createSymbolForConstantLike(
        ConstantLike $constant,
        File $file,
        ?string $containerName = null
    ): SymbolInformation {
        return new SymbolInformation(
            $constant->getName(),
            SymbolKind::CONSTANT,
            $constant->getIsDeprecated(),
            new Location($file->getUri(), $constant->getRange()),
            $containerName
        );
    }

    /**
     * @param FunctionLike $function
     * @param File         $file
     * @param string|null  $containerName
     *
     * @return SymbolInformation
     */
    private function createSymbolForFunctionLike(
        FunctionLike $function,
        File $file,
        ?string $containerName = null
    ): SymbolInformation {
        $kind = null;

        if ($function instanceof Method) {
            if ($function->getName() === '__construct') {
                $kind = SymbolKind::CONSTRUCTOR;
            } else {
                $kind = SymbolKind::METHOD;
            }
        } else {
            $kind = SymbolKind::FUNCTION_;
        }

        return new SymbolInformation(
            $function->getName(),
            $kind,
            $function->getIsDeprecated(),
            new Location($file->getUri(), $function->getRange()),
            $containerName
        );
    }

    /**
     * @param Property    $property
     * @param File        $file
     * @param string|null $containerName
     *
     * @return SymbolInformation
     */
    private function createSymbolForProperty(
        Property $property,
        File $file,
        ?string $containerName = null
    ): SymbolInformation {
        return new SymbolInformation(
            $property->getName(),
            SymbolKind::PROPERTY,
            $property->getIsDeprecated(),
            new Location($file->getUri(), $property->getRange()),
            $containerName
        );
    }

    /**
     * @param array $symbolList
     *
     * @return array
     */
    private function sortSymbolListByLocation(array $symbolList): array
    {
        usort($symbolList, function (SymbolInformation $a, SymbolInformation $b) {
            if ($a->getLocation()->getRange()->getStart()->liesAfter($b->getLocation()->getRange()->getStart())) {
                return 1;
            } elseif ($a->getLocation()->getRange()->getStart()->liesBefore(
                $b->getLocation()->getRange()->getStart()
            )) {
                return -1;
            }

            return 0;
        });

        return $symbolList;
    }
}
