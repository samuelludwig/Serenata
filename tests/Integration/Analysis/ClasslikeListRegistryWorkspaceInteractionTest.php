<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Workspace\Configuration\WorkspaceConfiguration;

use Serenata\Workspace\Workspace;

/**
 * Contains tests that test whether the registry properly interacts with workspace changes.
 */
class ClasslikeListRegistryWorkspaceInteractionTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testRegistryIsClearedWhenWorkspaceChanges(): void
    {
        $registry = $this->container->get('classlikeListProvider.registry');

        static::assertEmpty($registry->getAll());

        $registry->add([
            'fqcn' => '\Test',
        ]);

        static::assertCount(1, $registry->getAll());

        $this->container->get('managerRegistry')->setDatabaseUri(':memory:');
        $this->container->get('schemaInitializer')->initialize();
        $this->container->get('cacheClearingEventMediator.clearableCache')->clearCache();

        $this->container->get('activeWorkspaceManager')->setActiveWorkspace(new Workspace(new WorkspaceConfiguration(
            'test-id',
            [],
            ':memory:',
            7.1,
            [],
            ['php']
        )));

        static::assertEmpty($registry->getAll());
    }
}
