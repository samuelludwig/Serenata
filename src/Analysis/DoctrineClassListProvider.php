<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Conversion\ClasslikeConverter;

use PhpIntegrator\Analysis\Typing\FileClassListProviderInterface;

use PhpIntegrator\Indexing\Structures;
use PhpIntegrator\Indexing\ManagerRegistry;

/**
 * Retrieves a list of available classes via Doctrine.
 */
class DoctrineClassListProvider implements FileClassListProviderInterface
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
        $result = [];

        foreach ($this->managerRegistry->getRepository(Structures\Structure::class)->findAll() as $element) {
            $result[$element->getFqcn()] = $this->classlikeConverter->convert($element);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getAllForFile(string $file): array
    {
        $items = $this->managerRegistry->getRepository(Structures\Structure::class)->createQueryBuilder('entity')
            ->select('entity')
            ->innerJoin('entity.file', 'file')
            ->andWhere('file.path = :path')
            ->setParameter('path', $file)
            ->getQuery()
            ->execute();

        $result = [];

        foreach ($items as $element) {
            $result[$element->getFqcn()] = $this->classlikeConverter->convert($element);
        }

        return $result;
    }
}
