<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\Structures;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class InterfaceIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleInterface(): void
    {
        $structure = $this->indexInterface('SimpleInterface.phpt');

        static::assertSame('Test', $structure->getName());
        static::assertSame('\Test', $structure->getFqcn());
        static::assertSame($this->getPathFor('SimpleInterface.phpt'), $structure->getFile()->getPath());
        static::assertSame(3, $structure->getStartLine());
        static::assertSame(6, $structure->getEndLine());
        static::assertNull($structure->getShortDescription());
        static::assertNull($structure->getLongDescription());
        static::assertFalse($structure->getIsDeprecated());
        static::assertFalse($structure->getHasDocblock());
        static::assertCount(1, $structure->getConstants());
        static::assertEmpty($structure->getProperties());
        static::assertEmpty($structure->getMethods());
        static::assertEmpty($structure->getParentFqcns());
        static::assertEmpty($structure->getChildFqcns());
        static::assertEmpty($structure->getImplementorFqcns());
    }

    /**
     * @return void
     */
    public function testInterfaceNamespace(): void
    {
        $structure = $this->indexInterface('InterfaceNamespace.phpt');

        static::assertSame('\N\Test', $structure->getFqcn());
    }

    /**
     * @return void
     */
    public function testInterfaceShortDescription(): void
    {
        $structure = $this->indexInterface('InterfaceShortDescription.phpt');

        static::assertSame('A summary.', $structure->getShortDescription());
    }

    /**
     * @return void
     */
    public function testInterfaceLongDescription(): void
    {
        $structure = $this->indexInterface('InterfaceLongDescription.phpt');

        static::assertSame('A long description.', $structure->getLongDescription());
    }

    /**
     * @return void
     */
    public function testDeprecatedInterface(): void
    {
        $structure = $this->indexInterface('DeprecatedInterface.phpt');

        static::assertTrue($structure->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testInterfaceWithDocblock(): void
    {
        $structure = $this->indexInterface('InterfaceWithDocblock.phpt');

        static::assertTrue($structure->getHasDocblock());
    }

    /**
     * @return void
     */
    public function testInterfaceParentChildRelationship(): void
    {
        $path = $this->getPathFor('InterfaceParentChildRelationship.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

        static::assertCount(3, $entities);

        static::assertEmpty($entities[0]->getParentFqcns());
        static::assertCount(2, $entities[0]->getChildFqcns());
        static::assertSame($entities[1]->getFqcn(), $entities[0]->getChildFqcns()[0]);
        static::assertSame($entities[2]->getFqcn(), $entities[0]->getChildFqcns()[1]);

        static::assertCount(1, $entities[1]->getParentFqcns());
        static::assertSame($entities[0]->getFqcn(), $entities[1]->getParentFqcns()[0]);
        static::assertCount(1, $entities[1]->getChildFqcns());
        static::assertSame($entities[2]->getFqcn(), $entities[1]->getChildFqcns()[0]);

        static::assertCount(2, $entities[2]->getParentFqcns());
        static::assertSame($entities[0]->getFqcn(), $entities[2]->getParentFqcns()[0]);
        static::assertSame($entities[1]->getFqcn(), $entities[2]->getParentFqcns()[1]);
        static::assertEmpty($entities[2]->getChildFqcns());
    }

    /**
     * @return void
     */
    public function testInterfaceImplementor(): void
    {
        $structure = $this->indexInterface('InterfaceImplementor.phpt');

        static::assertCount(1, $structure->getImplementorFqcns());
        static::assertSame('\C', $structure->getImplementorFqcns()[0]);
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

            static::assertCount(1, $structures);

            $structure = $structures[0];

            static::assertSame('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

            static::assertCount(1, $structures);

            $structure = $structures[0];

            static::assertSame('Test2', $structure->getName());
        };

        $path = $this->getPathFor('InterfaceChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
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

        static::assertCount(1, $entities);

        return $entities[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return __DIR__ . '/InterfaceIndexingTest/' . $file;
    }
}
