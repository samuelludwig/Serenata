<?php

namespace PhpIntegrator\Analysis;

use RuntimeException;

use Doctrine\DBAL\Exception\DriverException;

use PhpIntegrator\Analysis\Conversion\ClasslikeConverter;

use PhpIntegrator\Analysis\Typing\FileClasslikeListProviderInterface;

use PhpIntegrator\Indexing\Structures;
use PhpIntegrator\Indexing\ManagerRegistry;

/**
 * Retrieves a list of available classlikes via Doctrine.
 */
class DoctrineClasslikeListProvider implements FileClasslikeListProviderInterface, ClasslikeListProviderInterface
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
                'file' => $file
            ]);
        } catch (DriverException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        return $this->mapClasslikes($items);
    }

    /**
     * @param array $classlikes
     *
     * @return array
     */
    protected function mapClasslikes(array $classlikes): array
    {
        $result = [];

        foreach ($classlikes as $element) {
            $result[$element->getFqcn()] = $this->classlikeConverter->convert($element);
        }

        return $result;
    }
}
