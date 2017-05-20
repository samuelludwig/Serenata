<?php

namespace PhpIntegrator\Indexing;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\Tools\SchemaTool;

/**
 * Initializes the database schema.
 */
class SchemaInitializer
{
    /**
     * @var int
     */
    public const SCHEMA_VERSION = 1;

    /**
     * @var int
     */
    public const VERSION_SETTING_NAME = 'version';

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
     * @return void
     */
    public function initialize(): void
    {
        $entityManager = $this->managerRegistry->getManager();

        $schemaTool = new SchemaTool($entityManager);

        // $schemaTool->dropDatabase();
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $this->loadFixtures();
    }

    /**
     * @return void
     */
    protected function loadFixtures(): void
    {
        $entityManager = $this->managerRegistry->getManager();

        $entityManager->persist(new Structures\StructureType('class'));
        $entityManager->persist(new Structures\StructureType('trait'));
        $entityManager->persist(new Structures\StructureType('interface'));

        $entityManager->persist(new Structures\AccessModifier('public'));
        $entityManager->persist(new Structures\AccessModifier('protected'));
        $entityManager->persist(new Structures\AccessModifier('private'));

        $entityManager->persist(new Structures\Setting(self::VERSION_SETTING_NAME, self::SCHEMA_VERSION));

        $entityManager->flush();
    }
}
