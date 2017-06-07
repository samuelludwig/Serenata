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

        $this->assertEquals('Test', $structure->getName());
        $this->assertEquals('\Test', $structure->getFqcn());
        $this->assertEquals($this->getPathFor('SimpleInterface.phpt'), $structure->getFile()->getPath());
        $this->assertEquals(3, $structure->getStartLine());
        $this->assertEquals(6, $structure->getEndLine());
        $this->assertNull($structure->getShortDescription());
        $this->assertNull($structure->getLongDescription());
        $this->assertFalse($structure->getIsDeprecated());
        $this->assertFalse($structure->getHasDocblock());
        $this->assertCount(1, $structure->getConstants());
        $this->assertEmpty($structure->getProperties());
        $this->assertEmpty($structure->getMethods());
        $this->assertEmpty($structure->getParentFqcns());
        $this->assertEmpty($structure->getChildFqcns());
        $this->assertEmpty($structure->getImplementorFqcns());
    }

    /**
     * @return void
     */
    public function testInterfaceNamespace(): void
    {
        $structure = $this->indexInterface('InterfaceNamespace.phpt');

        $this->assertEquals('\N\Test', $structure->getFqcn());
    }

    /**
     * @return void
     */
    public function testInterfaceShortDescription(): void
    {
        $structure = $this->indexInterface('InterfaceShortDescription.phpt');

        $this->assertEquals('A summary.', $structure->getShortDescription());
    }

    /**
     * @return void
     */
    public function testInterfaceLongDescription(): void
    {
        $structure = $this->indexInterface('InterfaceLongDescription.phpt');

        $this->assertEquals('A long description.', $structure->getLongDescription());
    }

    /**
     * @return void
     */
    public function testDeprecatedInterface(): void
    {
        $structure = $this->indexInterface('DeprecatedInterface.phpt');

        $this->assertTrue($structure->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testInterfaceWithDocblock(): void
    {
        $structure = $this->indexInterface('InterfaceWithDocblock.phpt');

        $this->assertTrue($structure->getHasDocblock());
    }

    // TODO: Test interface parents
    // TODO: Test interface children
    // TODO: Test interface implementors

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

            $this->assertCount(1, $structures);

            $structure = $structures[0];

            $this->assertEquals('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

            $this->assertCount(1, $structures);

            $structure = $structures[0];

            $this->assertEquals('Test2', $structure->getName());
        };

        $path = $this->getPathFor('InterfaceChanges.phpt');

        $this->assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Interface_
     */
    protected function indexInterface(string $file): Structures\Interface_
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Interface_::class)->findAll();

        $this->assertCount(1, $entities);

        return $entities[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/InterfaceIndexingTest/' . $file;
    }
}
