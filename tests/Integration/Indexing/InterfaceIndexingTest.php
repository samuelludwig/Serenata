<?php

namespace Serenata\Tests\Integration\Indexing;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class InterfaceIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleInterface(): void
    {
        $structure = $this->indexInterface('SimpleInterface.phpt');

        self::assertSame('Test', $structure->getName());
        self::assertSame('\Test', $structure->getFqcn());
        self::assertSame($this->normalizePath($this->getPathFor('SimpleInterface.phpt')), $structure->getFile()->getUri());
        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(5, 1)
            ),
            $structure->getRange()
        );
        self::assertNull($structure->getShortDescription());
        self::assertNull($structure->getLongDescription());
        self::assertFalse($structure->getIsDeprecated());
        self::assertFalse($structure->getHasDocblock());
        self::assertCount(1, $structure->getConstants());
        self::assertEmpty($structure->getProperties());
        self::assertEmpty($structure->getMethods());
        self::assertEmpty($structure->getParentFqcns());
        self::assertEmpty($structure->getChildFqcns());
        self::assertEmpty($structure->getImplementorFqcns());
    }

    /**
     * @return void
     */
    public function testInterfaceNamespace(): void
    {
        $structure = $this->indexInterface('InterfaceNamespace.phpt');

        self::assertSame('\N\Test', $structure->getFqcn());
    }

    /**
     * @return void
     */
    public function testInterfaceShortDescription(): void
    {
        $structure = $this->indexInterface('InterfaceShortDescription.phpt');

        self::assertSame('A summary.', $structure->getShortDescription());
    }

    /**
     * @return void
     */
    public function testInterfaceLongDescription(): void
    {
        $structure = $this->indexInterface('InterfaceLongDescription.phpt');

        self::assertSame('A long description.', $structure->getLongDescription());
    }

    /**
     * @return void
     */
    public function testDeprecatedInterface(): void
    {
        $structure = $this->indexInterface('DeprecatedInterface.phpt');

        self::assertTrue($structure->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testInterfaceWithDocblock(): void
    {
        $structure = $this->indexInterface('InterfaceWithDocblock.phpt');

        self::assertTrue($structure->getHasDocblock());
    }

    /**
     * @return void
     */
    public function testInterfaceParentChildRelationship(): void
    {
        $path = $this->getPathFor('InterfaceParentChildRelationship.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

        self::assertCount(3, $entities);

        self::assertEmpty($entities[0]->getParentFqcns());
        self::assertCount(2, $entities[0]->getChildFqcns());
        self::assertSame($entities[1]->getFqcn(), $entities[0]->getChildFqcns()[0]);
        self::assertSame($entities[2]->getFqcn(), $entities[0]->getChildFqcns()[1]);

        self::assertCount(1, $entities[1]->getParentFqcns());
        self::assertSame($entities[0]->getFqcn(), $entities[1]->getParentFqcns()[0]);
        self::assertCount(1, $entities[1]->getChildFqcns());
        self::assertSame($entities[2]->getFqcn(), $entities[1]->getChildFqcns()[0]);

        self::assertCount(2, $entities[2]->getParentFqcns());
        self::assertSame($entities[0]->getFqcn(), $entities[2]->getParentFqcns()[0]);
        self::assertSame($entities[1]->getFqcn(), $entities[2]->getParentFqcns()[1]);
        self::assertEmpty($entities[2]->getChildFqcns());
    }

    /**
     * @return void
     */
    public function testInterfaceImplementor(): void
    {
        $structure = $this->indexInterface('InterfaceImplementor.phpt');

        self::assertCount(1, $structure->getImplementorFqcns());
        self::assertSame('\C', $structure->getImplementorFqcns()[0]);
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

            self::assertCount(1, $structures);

            $structure = $structures[0];

            self::assertSame('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

            self::assertCount(1, $structures);

            $structure = $structures[0];

            self::assertSame('Test2', $structure->getName());
        };

        $path = $this->getPathFor('InterfaceChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Interface_
     */
    private function indexInterface(string $file): Structures\Interface_
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

        self::assertCount(1, $entities);

        return $entities[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/InterfaceIndexingTest/' . $file;
    }
}
