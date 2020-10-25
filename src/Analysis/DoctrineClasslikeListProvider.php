<?php

namespace Serenata\Analysis;

use Serenata\Analysis\Conversion\ClasslikeConverter;

use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

/**
 * Retrieves a list of available classlikes via Doctrine.
 */
final class DoctrineClasslikeListProvider implements FileClasslikeListProviderInterface, ClasslikeListProviderInterface
{
    /**
     * @var ClasslikeConverter
     */
    private $classlikeConverter;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ClasslikeConverter $classlikeConverter
     * @param ManagerRegistry    $managerRegistry
     */
    public function __construct(ClasslikeConverter $classlikeConverter, ManagerRegistry $managerRegistry)
    {
        $this->classlikeConverter = $classlikeConverter;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        $items = $this->managerRegistry->getRepository(Structures\Classlike::class)->findAll();

        return $this->mapClasslikes($items);
    }

    /**
     * @inheritDoc
     */
    public function getAllForFile(Structures\File $file): array
    {
        $items = $this->managerRegistry->getRepository(Structures\Classlike::class)->findBy([
            'file' => $file,
        ]);

        return $this->mapClasslikes($items);
    }

    /**
     * @param Structures\Classlike[] $classlikes
     *
     * @return array<string,array<string,mixed>>
     */
    private function mapClasslikes(array $classlikes): array
    {
        $result = [];

        foreach ($classlikes as $element) {
            $result[$element->getFqcn()] = $this->classlikeConverter->convert($element);
        }

        return $result;
    }
}
