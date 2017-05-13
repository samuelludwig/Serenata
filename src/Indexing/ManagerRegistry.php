<?php

namespace PhpIntegrator\Indexing;

use LogicException;

use Doctrine\ORM;

use Doctrine\Common\Persistence\AbstractManagerRegistry;

use Doctrine\DBAL\Driver\Connection;

use Doctrine\ORM\EntityManager;

/**
 * Handles indexation of PHP code.
 */
class ManagerRegistry extends AbstractManagerRegistry
{
    /**
     * @var SqliteConnectionFactory
     */
    private $sqliteConnectionFactory;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $databasePath;

    /**
     * @param SqliteConnectionFactory $sqliteConnectionFactory
     */
    public function __construct(SqliteConnectionFactory $sqliteConnectionFactory)
    {
        parent::__construct(
            'managerRegistry',
            [
                'default' => 'defaultConnection'
            ],
            [
                'default' => 'defaultEntityManager'
            ],
            'default',
            'default',
            ''
        );

        $this->sqliteConnectionFactory = $sqliteConnectionFactory;
    }

    /**
     * @inheritDoc
     */
    protected function getService($name)
    {
        if ($name === 'defaultConnection') {
            return $this->getConnectionInstance();
        } elseif ($name === 'defaultEntityManager') {
            return $this->getEntityManagerInstance();
        }

        throw new LogicException('Unknown manager service requested with name ' . $name);
    }

    /**
     * @return Connection
     */
    protected function getConnectionInstance(): Connection
    {
        if ($this->connection === null) {
            $this->connection = $this->sqliteConnectionFactory->create($this->getDatabasePath());
        }

        return $this->connection;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManagerInstance(): EntityManager
    {
        if ($this->entityManager === null) {
            $config = ORM\Tools\Setup::createXMLMetadataConfiguration([__DIR__ . "/Structures/Mapping"], true);

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
            $this->ensureConnectionClosed();
        } elseif ($name === 'defaultEntityManager') {
            $this->entityManager = null;
        }
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
    public function getDatabasePath(): string
    {
        return $this->databasePath;
    }

    /**
     * @param string $databasePath
     *
     * @return void
     */
    public function setDatabasePath(string $databasePath): void
    {
        $this->ensureConnectionClosed();

        $this->databasePath = $databasePath;
    }

    /**
     * @return void
     */
    public function ensureConnectionClosed(): void
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }
}
