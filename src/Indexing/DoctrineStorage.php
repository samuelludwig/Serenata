<?php

namespace PhpIntegrator\Indexing;

use UnexpectedValueException;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

use PhpIntegrator\Analysis\ClasslikeInfoBuilderProviderInterface;

use PhpIntegrator\Analysis\Typing\NamespaceImportProviderInterface;

use PhpIntegrator\Utility\NamespaceData;

/**
 * Storage backend that uses Doctrine.
 */
class DoctrineStorage implements StorageInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getFiles(): array
    {
        return $this->managerRegistry->getRepository(Structures\File::class)->findAll();
    }

    /**
     * @inheritDoc
     */
    public function getAccessModifiers(): array
    {
        return $this->managerRegistry->getRepository(Structures\AccessModifier::class)->findAll();
    }

    /**
     * @inheritDoc
     */
    public function getStructureTypes(): array
    {
        return $this->managerRegistry->getRepository(Structures\StructureType::class)->findAll();
    }

    /**
     * @inheritDoc
     */
    public function findStructureByFqcn(string $fqcn): ?Structures\Structure
    {
        return $this->managerRegistry->getRepository(Structures\Structure::class)->findOneBy([
            'fqcn' => $fqcn
        ]);
    }

    /**
     * @inheritDoc
     */
    public function findFileByPath(string $path): ?Structures\File
    {
        return $this->managerRegistry->getRepository(Structures\File::class)->findOneBy([
            'path' => $path
        ]);
    }

    /**
     * @inheritDoc
     */
    public function persist($entity): void
    {
        $this->managerRegistry->getManager()->persist($entity);
    }

    /**
     * @inheritDoc
     */
    public function delete($entity): void
    {
        $this->managerRegistry->getManager()->remove($entity);
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): void
    {
        $this->managerRegistry->getConnection()->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commitTransaction(): void
    {
        $this->managerRegistry->getManager()->flush();

        $this->managerRegistry->getConnection()->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollbackTransaction(): void
    {
        $this->managerRegistry->getConnection()->rollback();
    }
}
