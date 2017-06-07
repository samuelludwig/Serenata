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

        $this->assertTrue($structure instanceof Structures\Interface_);
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

    // TODO: Test interface namespace
    // TODO: Test interface short description
    // TODO: Test interface long description
    // TODO: Test interface deprecated
    // TODO: Test interface has docblock
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
