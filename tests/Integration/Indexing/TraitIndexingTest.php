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

        static::assertSame('Test', $structure->getName());
        static::assertSame('\Test', $structure->getFqcn());
        static::assertSame($this->getPathFor('SimpleTrait.phpt'), $structure->getFile()->getPath());
        static::assertSame(3, $structure->getStartLine());
        static::assertSame(6, $structure->getEndLine());
        static::assertNull($structure->getShortDescription());
        static::assertNull($structure->getLongDescription());
        static::assertFalse($structure->getIsDeprecated());
        static::assertFalse($structure->getHasDocblock());
        static::assertCount(1, $structure->getConstants());
        static::assertEmpty($structure->getProperties());
        static::assertEmpty($structure->getMethods());
        static::assertEmpty($structure->getTraitFqcns());
        static::assertEmpty($structure->getTraitUserFqcns());
        static::assertEmpty($structure->getTraitAliases());
        static::assertEmpty($structure->getTraitPrecedences());
    }

    /**
     * @return void
     */
    public function testTraitNamespace(): void
    {
        $structure = $this->indexTrait('TraitNamespace.phpt');

        static::assertSame('\N\Test', $structure->getFqcn());
    }

    /**
     * @return void
     */
    public function testTraitShortDescription(): void
    {
        $structure = $this->indexTrait('TraitShortDescription.phpt');

        static::assertSame('A summary.', $structure->getShortDescription());
    }

    /**
     * @return void
     */
    public function testTraitLongDescription(): void
    {
        $structure = $this->indexTrait('TraitLongDescription.phpt');

        static::assertSame('A long description.', $structure->getLongDescription());
    }

    /**
     * @return void
     */
    public function testDeprecatedTrait(): void
    {
        $structure = $this->indexTrait('DeprecatedTrait.phpt');

        static::assertTrue($structure->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testTraitWithDocblock(): void
    {
        $structure = $this->indexTrait('TraitWithDocblock.phpt');

        static::assertTrue($structure->getHasDocblock());
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

        static::assertCount(3, $entities);

        $structure = $entities[2];

        static::assertCount(2, $structure->getTraitFqcns());
        static::assertSame('\A', $structure->getTraitFqcns()[0]);
        static::assertSame('\B', $structure->getTraitFqcns()[1]);
    }

    /**
     * @return void
     */
    public function testTraitTraitAlias(): void
    {
        $path = $this->getPathFor('TraitTraitAlias.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        static::assertCount(2, $entities);

        $structure = $entities[1];

        static::assertCount(1, $structure->getTraitAliases());
        static::assertSame($structure, $structure->getTraitAliases()[0]->getTrait());
        static::assertNull($structure->getTraitAliases()[0]->getTraitFqcn());
        static::assertNull($structure->getTraitAliases()[0]->getAccessModifier());
        static::assertSame('foo', $structure->getTraitAliases()[0]->getName());
        static::assertSame('bar', $structure->getTraitAliases()[0]->getAlias());
    }

    /**
     * @return void
     */
    public function testTraitTraitAliasWithTraitName(): void
    {
        $path = $this->getPathFor('TraitTraitAliasWithTraitName.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        static::assertCount(2, $entities);

        $structure = $entities[1];

        static::assertCount(1, $structure->getTraitAliases());
        static::assertSame('\A', $structure->getTraitAliases()[0]->getTraitFqcn());
    }

    /**
     * @return void
     */
    public function testTraitTraitAliasWithAccessModifier(): void
    {
        $path = $this->getPathFor('TraitTraitAliasWithAccessModifier.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        static::assertCount(2, $entities);

        $structure = $entities[1];

        static::assertCount(1, $structure->getTraitAliases());
        static::assertNotNull($structure->getTraitAliases()[0]->getAccessModifier());
        static::assertSame('protected', $structure->getTraitAliases()[0]->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testTraitTraitPrecedence(): void
    {
        $path = $this->getPathFor('TraitTraitPrecedence.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        static::assertCount(3, $entities);

        $structure = $entities[2];

        static::assertCount(1, $structure->getTraitPrecedences());
        static::assertSame($structure, $structure->getTraitPrecedences()[0]->getTrait());
        static::assertSame('\A', $structure->getTraitPrecedences()[0]->getTraitFqcn());
        static::assertSame('foo', $structure->getTraitPrecedences()[0]->getName());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

            static::assertCount(1, $structures);

            $structure = $structures[0];

            static::assertSame('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

            static::assertCount(1, $structures);

            $structure = $structures[0];

            static::assertSame('Test2', $structure->getName());
        };

        $path = $this->getPathFor('TraitChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Trait_
     */
    private function indexTrait(string $file): Structures\Trait_
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

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
        return __DIR__ . '/TraitIndexingTest/' . $file;
    }
}
