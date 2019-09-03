<?php

namespace Serenata\CodeLenses;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Commands\OpenTextDocumentCommand;

use Serenata\Indexing\StorageInterface;

use Serenata\Utility\TextDocumentItem;

/**
 * Retrieves a list of code lenses for a document.
 */
final class CodeLensesRetriever
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var FileClasslikeListProviderInterface
     */
    private $fileClasslikeListProvider;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @param StorageInterface                   $storage
     * @param FileClasslikeListProviderInterface $fileClasslikeListProvider
     * @param ClasslikeInfoBuilderInterface      $classlikeInfoBuilder
     */
    public function __construct(
        StorageInterface $storage,
        FileClasslikeListProviderInterface $fileClasslikeListProvider,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder
    ) {
        $this->storage = $storage;
        $this->fileClasslikeListProvider = $fileClasslikeListProvider;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     *
     * @return CodeLens[]|null
     */
    public function retrieve(TextDocumentItem $textDocumentItem): ?array
    {
        $lenses = [];
        $file = $this->storage->getFileByUri($textDocumentItem->getUri());

        foreach ($this->fileClasslikeListProvider->getAllForFile($file) as $classlike) {
            $lenses = array_merge($lenses, $this->generateOverrideAndImplementationLenses($classlike['fqcn']));
        }

        return $lenses;
    }

    /**
     * @param string $fqcn
     *
     * @return CodeLens[]
     */
    private function generateOverrideAndImplementationLenses(string $fqcn): array
    {
        $lenses = [];
        $classlikeInfo = $this->classlikeInfoBuilder->build($fqcn);

        return array_merge(
            $this->generateMethodOverrideAndImplementationLenses($classlikeInfo),
            $this->generatePropertyOverrideLenses($classlikeInfo)
        );
    }

    /**
     * @param array $classlikeInfo
     *
     * @return array
     */
    private function generateMethodOverrideAndImplementationLenses(array $classlikeInfo): array
    {
        $lenses = [];

        foreach ($classlikeInfo['methods'] as $method) {
            $referencedMethodInfo = [];

            if ($method['override']) {
                $referencedMethodInfo = $method['override'];
            } elseif ($method['implementations']) {
                $referencedMethodInfo = $method['implementations'][0];
            } else {
                continue; // Not relevant.
            }

            if (!$method['override'] && !$method['implementations']) {
                continue; // Not relevant.
            } elseif ($method['declaringStructure']['fqcn'] !== $classlikeInfo['fqcn']) {
                continue; // Not actually located anywhere in this class' file.
            }

            $lenses[] = new CodeLens(
                $method['range'],
                new OpenTextDocumentCommand(
                    $method['override'] ? 'Override' : 'Implementation',
                    $referencedMethodInfo['declaringStructure']['uri'],
                    $referencedMethodInfo['declaringStructure']['memberRange']->getStart()
                ),
                null
            );
        }

        return $lenses;
    }

    /**
     * @param array $classlikeInfo
     *
     * @return array
     */
    private function generatePropertyOverrideLenses(array $classlikeInfo): array
    {
        $lenses = [];

        foreach ($classlikeInfo['properties'] as $property) {
            if (!$property['override']) {
                continue; // Not relevant.
            } elseif ($property['declaringStructure']['fqcn'] !== $classlikeInfo['fqcn']) {
                continue; // Not actually located anywhere in this class' file.
            }

            $lenses[] = new CodeLens(
                $property['range'],
                new OpenTextDocumentCommand(
                    'Override',
                    $property['override']['declaringStructure']['uri'],
                    $property['override']['declaringStructure']['memberRange']->getStart()
                ),
                null
            );
        }

        return $lenses;
    }
}
