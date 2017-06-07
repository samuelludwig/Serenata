<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\Structures;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class TraitIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleTrait(): void
    {
        $structure = $this->indexTrait('SimpleTrait.phpt');

        $this->assertEquals('Test', $structure->getName());
        $this->assertEquals('\Test', $structure->getFqcn());
        $this->assertEquals($this->getPathFor('SimpleTrait.phpt'), $structure->getFile()->getPath());
        $this->assertEquals(3, $structure->getStartLine());
        $this->assertEquals(6, $structure->getEndLine());
        $this->assertNull($structure->getShortDescription());
        $this->assertNull($structure->getLongDescription());
        $this->assertFalse($structure->getIsDeprecated());
        $this->assertFalse($structure->getHasDocblock());
        $this->assertCount(1, $structure->getConstants());
        $this->assertEmpty($structure->getProperties());
        $this->assertEmpty($structure->getMethods());
        $this->assertEmpty($structure->getTraitFqcns());
        $this->assertEmpty($structure->getTraitUserFqcns());
        $this->assertEmpty($structure->getTraitAliases());
        $this->assertEmpty($structure->getTraitPrecedences());
    }

    // TODO: Test trait namespace
    // TODO: Test trait short description
    // TODO: Test trait long description
    // TODO: Test trait deprecated
    // TODO: Test trait has docblock
    // TODO: Test trait traits
    // TODO: Test trait trait users
    // TODO: Test trait trait predecences
    // TODO: Test trait trait aliases

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

            $this->assertCount(1, $structures);

            $structure = $structures[0];

            $this->assertEquals('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

            $this->assertCount(1, $structures);

            $structure = $structures[0];

            $this->assertEquals('Test2', $structure->getName());
        };

        $path = $this->getPathFor('TraitChanges.phpt');

        $this->assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Trait_
     */
    protected function indexTrait(string $file): Structures\Trait_
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

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
        return __DIR__ . '/TraitIndexingTest/' . $file;
    }
}
