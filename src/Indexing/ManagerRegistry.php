<?php

namespace Serenata\Indexing;

use LogicException;

use Doctrine\ORM;

use Doctrine\Common\Cache\Cache;

use Doctrine\Persistence\AbstractManagerRegistry;

use Doctrine\DBAL\Connection;

use Doctrine\ORM\EntityManager;

use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;

/**
 * Handles indexation of PHP code.
 */
final class ManagerRegistry extends AbstractManagerRegistry implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var SqliteConnectionFactory
     */
    private $sqliteConnectionFactory;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Connection|null
     */
    private $connection;

    /**
     * @var EntityManager|null
     */
    private $entityManager;

    /**
     * @var string
     */
    private $databaseUri;

    /**
     * @param SqliteConnectionFactory $sqliteConnectionFactory
     * @param Cache                   $cache
     */
    public function __construct(SqliteConnectionFactory $sqliteConnectionFactory, Cache $cache)
    {
        parent::__construct(
            'managerRegistry',
            [
                'default' => 'defaultConnection',
            ],
            [
                'default' => 'defaultEntityManager',
            ],
            'default',
            'default',
            ORM\Proxy\Proxy::class
        );

        $this->sqliteConnectionFactory = $sqliteConnectionFactory;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    protected function getService($name)
    {
        if ($name === 'defaultConnection') {
            /** @var EntityManager $connection Because overridden method docblock return type is incorrect. */
            $connection = $this->getConnectionInstance();

            return $connection;
        } elseif ($name === 'defaultEntityManager') {
            return $this->getEntityManagerInstance();
        }

        throw new LogicException('Unknown manager service requested with name ' . $name);
    }

    /**
     * @return Connection
     */
    private function getConnectionInstance(): Connection
    {
        if ($this->connection === null) {
            $this->connection = $this->sqliteConnectionFactory->create($this->getDatabaseUri());
        }

        return $this->connection;
    }

    /**
     * @return EntityManager
     */
    private function getEntityManagerInstance(): EntityManager
    {
        if ($this->entityManager === null || !$this->entityManager->isOpen()) {
            $regionConfig = new RegionsConfiguration();
            $cacheFactory = new DefaultCacheFactory($regionConfig, $this->cache);

            $config = ORM\Tools\Setup::createXMLMetadataConfiguration([__DIR__ . "/Structures/Mapping"], true);
            $config->setSecondLevelCacheEnabled();

            $secondLevelCacheConfiguration = $config->getSecondLevelCacheConfiguration();

            assert($secondLevelCacheConfiguration !== null);

            $secondLevelCacheConfiguration->setCacheFactory($cacheFactory);

            $this->entityManager = EntityManager::create($this->getConnectionInstance(), $config);
        }

        return $this->entityManager;
    }

    /**
     * @inheritDoc
     */
    protected function resetService($name)
    {
        if ($name === 'defaultConnection') {
            if ($this->connection !== null) {
                $this->connection->close();
                $this->connection = null;
            }

            // Entity manager depends on connection, cascade reset.
            $this->resetService('defaultEntityManager');
        } elseif ($name === 'defaultEntityManager') {
            if ($this->entityManager !== null) {
                $this->entityManager->close();
                $this->entityManager = null;
            }
        }
    }

    /**
     * @return void
     */
    public function ensureConnectionClosed(): void
    {
        // Ensure all entity objects are freed and their memory can be reclaimed.
        $this->getService('defaultEntityManager')->clear();

        $this->resetService('defaultConnection');
    }

    /**
     * @inheritDoc
     */
    public function getAliasNamespace($alias)
    {
        return $alias;
    }

    /**
     * @return string
     */
    public function getDatabaseUri(): string
    {
        return $this->databaseUri;
    }

    /**
     * @param string $databaseUri
     *
     * @return void
     */
    public function setDatabaseUri(string $databaseUri): void
    {
        $this->databaseUri = $databaseUri;

        $this->resetService('defaultConnection');

        $this->emit(WorkspaceEventName::CHANGED, [$databaseUri]);
    }
}
