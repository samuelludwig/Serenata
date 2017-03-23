<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Conversion\ClasslikeConverter;
use PhpIntegrator\Analysis\Typing\FileClassListProviderInterface;

use PhpIntegrator\Indexing\IndexDatabase;

/**
 * Retrieves a list of available classes.
 */
class ClassListProvider implements FileClassListProviderInterface
{
    /**
     * @var ClasslikeConverter
     */
    private $classlikeConverter;

    /**
     * @var IndexDatabase
     */
    private $indexDatabase;

    /**
     * @param ClasslikeConverter $classlikeConverter
     * @param IndexDatabase      $indexDatabase
     */
    public function __construct(ClasslikeConverter $classlikeConverter, IndexDatabase $indexDatabase)
    {
        $this->classlikeConverter = $classlikeConverter;
        $this->indexDatabase = $indexDatabase;
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
            $result[$element['fqcn']] = $this->classlikeConverter->convert($element);
        }

        return $result;
    }
}
