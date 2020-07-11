<?php

namespace Serenata\Tests\Integration\Indexing;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TraitIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleTrait(): void
    {
        $structure = $this->indexTrait('SimpleTrait.phpt');

        self::assertSame('Test', $structure->getName());
        self::assertSame('\Test', $structure->getFqcn());
        self::assertSame($this->normalizePath($this->getPathFor('SimpleTrait.phpt')), $structure->getFile()->getUri());
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
        self::assertEmpty($structure->getTraitFqcns());
        self::assertEmpty($structure->getTraitUserFqcns());
        self::assertEmpty($structure->getTraitAliases());
        self::assertEmpty($structure->getTraitPrecedences());
    }

    /**
     * @return void
     */
    public function testTraitNamespace(): void
    {
        $structure = $this->indexTrait('TraitNamespace.phpt');

        self::assertSame('\N\Test', $structure->getFqcn());
    }

    /**
     * @return void
     */
    public function testTraitShortDescription(): void
    {
        $structure = $this->indexTrait('TraitShortDescription.phpt');

        self::assertSame('A summary.', $structure->getShortDescription());
    }

    /**
     * @return void
     */
    public function testTraitLongDescription(): void
    {
        $structure = $this->indexTrait('TraitLongDescription.phpt');

        self::assertSame('A long description.', $structure->getLongDescription());
    }

    /**
     * @return void
     */
    public function testDeprecatedTrait(): void
    {
        $structure = $this->indexTrait('DeprecatedTrait.phpt');

        self::assertTrue($structure->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testTraitWithDocblock(): void
    {
        $structure = $this->indexTrait('TraitWithDocblock.phpt');

        self::assertTrue($structure->getHasDocblock());
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

        self::assertCount(3, $entities);

        $structure = $entities[2];

        self::assertCount(2, $structure->getTraitFqcns());
        self::assertSame('\A', $structure->getTraitFqcns()[0]);
        self::assertSame('\B', $structure->getTraitFqcns()[1]);
    }

    /**
     * @return void
     */
    public function testTraitTraitAlias(): void
    {
        $path = $this->getPathFor('TraitTraitAlias.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        self::assertCount(2, $entities);

        $structure = $entities[1];

        self::assertCount(1, $structure->getTraitAliases());
        self::assertSame($structure, $structure->getTraitAliases()[0]->getTrait());
        self::assertNull($structure->getTraitAliases()[0]->getTraitFqcn());
        self::assertNull($structure->getTraitAliases()[0]->getAccessModifier());
        self::assertSame('foo', $structure->getTraitAliases()[0]->getName());
        self::assertSame('bar', $structure->getTraitAliases()[0]->getAlias());
    }

    /**
     * @return void
     */
    public function testTraitTraitAliasWithTraitName(): void
    {
        $path = $this->getPathFor('TraitTraitAliasWithTraitName.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        self::assertCount(2, $entities);

        $structure = $entities[1];

        self::assertCount(1, $structure->getTraitAliases());
        self::assertSame('\A', $structure->getTraitAliases()[0]->getTraitFqcn());
    }

    /**
     * @return void
     */
    public function testTraitTraitAliasWithAccessModifier(): void
    {
        $path = $this->getPathFor('TraitTraitAliasWithAccessModifier.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        self::assertCount(2, $entities);

        $structure = $entities[1];

        self::assertCount(1, $structure->getTraitAliases());
        self::assertNotNull($structure->getTraitAliases()[0]->getAccessModifier());
        self::assertSame('protected', $structure->getTraitAliases()[0]->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testTraitTraitPrecedence(): void
    {
        $path = $this->getPathFor('TraitTraitPrecedence.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

        self::assertCount(3, $entities);

        $structure = $entities[2];

        self::assertCount(1, $structure->getTraitPrecedences());
        self::assertSame($structure, $structure->getTraitPrecedences()[0]->getTrait());
        self::assertSame('\A', $structure->getTraitPrecedences()[0]->getTraitFqcn());
        self::assertSame('foo', $structure->getTraitPrecedences()[0]->getName());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

            self::assertCount(1, $structures);

            $structure = $structures[0];

            self::assertSame('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Trait_::class)->findAll();

            self::assertCount(1, $structures);

            $structure = $structures[0];

            self::assertSame('Test2', $structure->getName());
        };

        $path = $this->getPathFor('TraitChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
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
        return 'file:///' . __DIR__ . '/TraitIndexingTest/' . $file;
    }
}
