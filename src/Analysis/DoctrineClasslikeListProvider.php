<?php

namespace Serenata\Analysis;

use RuntimeException;

use Doctrine\DBAL\Exception\DriverException;

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
        try {
            $items = $this->managerRegistry->getRepository(Structures\Classlike::class)->findAll();
        } catch (DriverException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        return $this->mapClasslikes($items);
    }

    /**
     * @inheritDoc
     */
    public function getAllForFile(Structures\File $file): array
    {
        try {
            $items = $this->managerRegistry->getRepository(Structures\Classlike::class)->findBy([
                'file' => $file,
            ]);
        } catch (DriverException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

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
