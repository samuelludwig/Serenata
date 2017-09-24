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

        $this->assertSame('Test', $structure->getName());
        $this->assertSame('\Test', $structure->getFqcn());
        $this->assertSame($this->getPathFor('SimpleTrait.phpt'), $structure->getFile()->getPath());
        $this->assertSame(3, $structure->getStartLine());
        $this->assertSame(6, $structure->getEndLine());
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

    /**
     * @return void
     */
    public function testTraitNamespace(): void
    {
        $structure = $this->indexTrait('TraitNamespace.phpt');

        $this->assertSame('\N\Test', $structure->getFqcn());
    }

    /**
     * @return void
     */
    public function testTraitShortDescription(): void
    {
        $structure = $this->indexTrait('TraitShortDescription.phpt');

        $this->assertSame('A summary.', $structure->getShortDescription());
    }

    /**
     * @return void
     */
    public function testTraitLongDescription(): void
    {
        $structure = $this->indexTrait('TraitLongDescription.phpt');

        $this->assertSame('A long description.', $structure->getLongDescription());
    }

    /**
     * @return void
     */
    public function testDeprecatedTrait(): void
    {
        $structure = $this->indexTrait('DeprecatedTrait.phpt');

        $this->assertTrue($structure->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testTraitWithDocblock(): void
    {
        $structure = $this->indexTrait('TraitWithDocblock.phpt');

        $this->assertTrue($structure->getHasDocblock());
    }


    // TODO: Test trait trait users


    /**
     * @return void
     */
    public function testTraitTrait(): void
    {
        $path = $this->getPathFor('TraitTrait.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        $this->assertCount(3, $entities);

        $structure = $entities[2];

        $this->assertCount(2, $structure->getTraitFqcns());
        $this->assertSame('\A', $structure->getTraitFqcns()[0]);
        $this->assertSame('\B', $structure->getTraitFqcns()[1]);
    }

    /**
     * @return void
     */
    public function testTraitTraitAlias(): void
    {
        $path = $this->getPathFor('TraitTraitAlias.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        $this->assertCount(2, $entities);

        $structure = $entities[1];

        $this->assertCount(1, $structure->getTraitAliases());
        $this->assertSame($structure, $structure->getTraitAliases()[0]->getTrait());
        $this->assertNull($structure->getTraitAliases()[0]->getTraitFqcn());
        $this->assertNull($structure->getTraitAliases()[0]->getAccessModifier());
        $this->assertSame('foo', $structure->getTraitAliases()[0]->getName());
        $this->assertSame('bar', $structure->getTraitAliases()[0]->getAlias());
    }

    /**
     * @return void
     */
    public function testTraitTraitAliasWithTraitName(): void
    {
        $path = $this->getPathFor('TraitTraitAliasWithTraitName.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        $this->assertCount(2, $entities);

        $structure = $entities[1];

        $this->assertCount(1, $structure->getTraitAliases());
        $this->assertSame('\A', $structure->getTraitAliases()[0]->getTraitFqcn());
    }

    /**
     * @return void
     */
    public function testTraitTraitAliasWithAccessModifier(): void
    {
        $path = $this->getPathFor('TraitTraitAliasWithAccessModifier.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        $this->assertCount(2, $entities);

        $structure = $entities[1];

        $this->assertCount(1, $structure->getTraitAliases());
        $this->assertNotNull($structure->getTraitAliases()[0]->getAccessModifier());
        $this->assertSame('protected', $structure->getTraitAliases()[0]->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testTraitTraitPrecedence(): void
    {
        $path = $this->getPathFor('TraitTraitPrecedence.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        $this->assertCount(3, $entities);

        $structure = $entities[2];

        $this->assertCount(1, $structure->getTraitPrecedences());
        $this->assertSame($structure, $structure->getTraitPrecedences()[0]->getTrait());
        $this->assertSame('\A', $structure->getTraitPrecedences()[0]->getTraitFqcn());
        $this->assertSame('foo', $structure->getTraitPrecedences()[0]->getName());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

            $this->assertCount(1, $structures);

            $structure = $structures[0];

            $this->assertSame('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

            $this->assertCount(1, $structures);

            $structure = $structures[0];

            $this->assertSame('Test2', $structure->getName());
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
