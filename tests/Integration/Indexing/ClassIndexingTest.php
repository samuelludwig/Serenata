<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\Structures;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClassIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleClass(): void
    {
        $structure = $this->indexClass('SimpleClass.phpt');

        $this->assertTrue($structure instanceof Structures\Class_);
        $this->assertEquals('Test', $structure->getName());
        $this->assertEquals('\Test', $structure->getFqcn());
        $this->assertEquals($this->getPathFor('SimpleClass.phpt'), $structure->getFile()->getPath());
        $this->assertEquals(3, $structure->getStartLine());
        $this->assertEquals(6, $structure->getEndLine());
        $this->assertNull($structure->getShortDescription());
        $this->assertNull($structure->getLongDescription());
        $this->assertFalse($structure->getIsDeprecated());
        $this->assertFalse($structure->getHasDocblock());
        $this->assertCount(1, $structure->getConstants());
        $this->assertEmpty($structure->getProperties());
        $this->assertEmpty($structure->getMethods());
        $this->assertFalse($structure->getIsAbstract());
        $this->assertFalse($structure->getIsFinal());
        $this->assertFalse($structure->getIsAnnotation());
        $this->assertNull($structure->getParentFqcn());
        $this->assertEmpty($structure->getChildFqcns());
        $this->assertEmpty($structure->getInterfaceFqcns());
        $this->assertEmpty($structure->getTraitFqcns());
        $this->assertEmpty($structure->getTraitAliases());
        $this->assertEmpty($structure->getTraitPrecedences());
    }

    // TODO: Test class namespace
    // TODO: Test class short description
    // TODO: Test class long description
    // TODO: Test class deprecated
    // TODO: Test class has docblock
    // TODO: Test class is abstract
    // TODO: Test class is final
    // TODO: Test class is annotation
    // TODO: Test class parent
    // TODO: Test class children
    // TODO: Test class interfaces
    // TODO: Test class traits
    // TODO: Test class trait aliases
    // TODO: Test class trait precedences

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            $this->assertCount(1, $structures);

            $structure = $structures[0];

            $this->assertEquals('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            $this->assertCount(1, $structures);

            $structure = $structures[0];

            $this->assertEquals('Test2', $structure->getName());
        };

        $path = $this->getPathFor('ClassChanges.phpt');

        $this->assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Class_
     */
    protected function indexClass(string $file): Structures\Class_
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

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
        return __DIR__ . '/ClassIndexingTest/' . $file;
    }
}
