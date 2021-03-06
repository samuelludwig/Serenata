<?php

namespace Serenata\Indexing;

use Throwable;
use LogicException;

use Doctrine\DBAL\Connection;

use Doctrine\Persistence\ManagerRegistry;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;

use Serenata\Analysis\MetadataProviderInterface;

/**
 * Storage backend that uses Doctrine.
 */
final class DoctrineStorage implements StorageInterface, MetadataProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var PathNormalizer
     */
    private $pathNormalizer;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        PathNormalizer $pathNormalizer
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->pathNormalizer = $pathNormalizer;
    }

    /**
     * @inheritDoc
     */
    public function getFiles(): array
    {
        try {
            return $this->managerRegistry->getRepository(Structures\File::class)->findAll();
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }
    }

    /**
     * @inheritDoc
     */
    public function getAccessModifiers(): array
    {
        try {
            return $this->managerRegistry->getRepository(Structures\AccessModifier::class)->findAll();
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }
    }

    /**
     * @inheritDoc
     */
    public function findStructureByFqcn(string $fqcn): ?Structures\Classlike
    {
        try {
            return $this->managerRegistry->getRepository(Structures\Classlike::class)->findOneBy([
                'fqcn' => $fqcn,
            ]);
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }
    }

    /**
     * @inheritDoc
     */
    public function getFileByUri(string $uri): Structures\File
    {
        try {
            $file = $this->managerRegistry->getRepository(Structures\File::class)->findOneBy([
                'uri' => $this->pathNormalizer->normalize($uri),
            ]);
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }

        if ($file === null) {
            throw new FileNotFoundStorageException("Could not find file \"{$uri}\" in index");
        }

        return $file;
    }

    /**
     * @inheritDoc
     */
    public function persist($entity): void
    {
        try {
            $this->managerRegistry->getManager()->persist($entity);
            $this->managerRegistry->getManager()->flush();
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($entity): void
    {
        try {
            $this->managerRegistry->getManager()->remove($entity);
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): void
    {
        try {
            $this->getConnection()->beginTransaction();
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }
    }

    /**
     * @inheritDoc
     */
    public function commitTransaction(): void
    {
        try {
            $this->managerRegistry->getManager()->flush();

            $this->getConnection()->commit();
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }
    }

    /**
     * @inheritDoc
     */
    public function rollbackTransaction(): void
    {
        try {
            $this->getConnection()->rollBack();
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }
    }

    /**
     * @inheritDoc
     */
    public function getMetaStaticMethodTypesFor(string $fqcn, string $method): array
    {
        try {
            return $this->managerRegistry->getRepository(Structures\MetaStaticMethodType::class)->findBy([
                'fqcn' => $fqcn,
                'name' => $method,
            ]);
        } catch (Throwable $t) {
            $this->handleThrowable($t);
        }
    }

    /**
     * @return Connection
     */
    private function getConnection(): Connection
    {
        $connection = $this->managerRegistry->getConnection();

        assert($connection instanceof Connection);

        return $connection;
    }

    /**
     * @param Throwable $throwable
     *
     * @throws Throwable
     *
     * @return never
     */
    private function handleThrowable(Throwable $throwable): void
    {
        if ($throwable instanceof DriverException) {
            if ($throwable instanceof LockWaitTimeoutException) {
                // Not strictly a bug in the code, but this kind of error is fatal and currently not automagically
                // fixed, so the user should know about it, rather than have the server not work properly.
                throw new LogicException($throwable->getMessage(), 0, $throwable);
            } elseif (mb_strpos($throwable->getMessage(), 'disk I/O error') !== false) {
                // Same as above.
                throw new LogicException($throwable->getMessage(), 0, $throwable);
            }

            throw new StorageException($throwable->getMessage(), 0, $throwable);
        }

        throw $throwable;
    }
}
