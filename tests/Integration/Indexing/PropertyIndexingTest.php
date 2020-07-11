<?php

namespace Serenata\Tests\Integration\Indexing;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Indexing\Structures\AccessModifierNameValue;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PropertyIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleProperty(): void
    {
        $property = $this->indexProperty('SimpleProperty.phpt');

        self::assertSame('foo', $property->getName());
        self::assertSame($this->normalizePath($this->getPathFor('SimpleProperty.phpt')), $property->getFile()->getUri());
        self::assertEquals(
            new Range(
                new Position(4, 4),
                new Position(4, 24)
            ),
            $property->getRange()
        );
        self::assertSame("'test'", $property->getDefaultValue());
        self::assertFalse($property->getIsDeprecated());
        self::assertFalse($property->getIsMagic());
        self::assertFalse($property->getIsStatic());
        self::assertFalse($property->getHasDocblock());
        self::assertNull($property->getShortDescription());
        self::assertNull($property->getLongDescription());
        self::assertNull($property->getTypeDescription());
        self::assertSame('string', (string) $property->getType());
        self::assertSame(AccessModifierNameValue::PUBLIC_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testDeprecatedProperty(): void
    {
        $property = $this->indexProperty('DeprecatedProperty.phpt');

        self::assertTrue($property->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testStaticProperty(): void
    {
        $property = $this->indexProperty('StaticProperty.phpt');

        self::assertTrue($property->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicProperty(): void
    {
        $property = $this->indexProperty('MagicProperty.phpt');

        self::assertTrue($property->getIsMagic());
    }

    /**
     * @return void
     */
    public function testMagicStaticProperty(): void
    {
        $property = $this->indexProperty('MagicStaticProperty.phpt');

        self::assertTrue($property->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicPropertyWithDescription(): void
    {
        $property = $this->indexProperty('MagicPropertyWithDescription.phpt');

        self::assertSame('A description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testMagicPropertyTypeResolution(): void
    {
        $property = $this->indexProperty('MagicPropertyTypeResolution.phpt');

        self::assertSame('\N\A', (string) $property->getType());
    }

    /**
     * @return void
     */
    public function testMagicReadProperty(): void
    {
        $property = $this->indexProperty('MagicReadProperty.phpt');

        self::assertTrue($property->getIsMagic());
    }

    /**
     * @return void
     */
    public function testMagicWriteProperty(): void
    {
        $property = $this->indexProperty('MagicWriteProperty.phpt');

        self::assertTrue($property->getIsMagic());
    }

    /**
     * @return void
     */
    public function testPropertyShortDescription(): void
    {
        $property = $this->indexProperty('PropertyShortDescription.phpt');

        self::assertSame('This is a summary.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testPropertyLongDescription(): void
    {
        $property = $this->indexProperty('PropertyLongDescription.phpt');

        self::assertSame('This is a long description.', $property->getLongDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeDescription(): void
    {
        $property = $this->indexProperty('PropertyTypeDescription.phpt');

        self::assertSame('This is a type description.', $property->getTypeDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeDescriptionIsUsedAsSummaryIfSummaryIsMissing(): void
    {
        $property = $this->indexProperty('PropertyTypeDescriptionAsSummary.phpt');

        self::assertSame('This is a type description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeDescriptionTakesPrecedenceOverSummary(): void
    {
        $property = $this->indexProperty('PropertyTypeDescriptionTakesPrecedenceOverSummary.phpt');

        self::assertSame('This is a type description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeIsFetchedFromDocblockAndGetsPrecedenceOverDefaultValueType(): void
    {
        $property = $this->indexProperty('PropertyTypeFromDocblock.phpt');

        self::assertSame('int', (string) $property->getType());
    }

    /**
     * @return void
     */
    public function testPropertyTypeIsFetchedFromDefinitionAndGetsPrecedenceOverDefaultValueType(): void
    {
        $property = $this->indexProperty('TypedProperty.phpt');

        self::assertSame('(string | null)', (string) $property->getType());
    }

    /**
     * @return void
     */
    public function testPropertyTypeInDocblockGetsPrecedenceOverTypeInDefinition(): void
    {
        $property = $this->indexProperty('TypedPropertyWithDocblockOverride.phpt');

        self::assertSame('\DateTime', (string) $property->getType());
    }

    /**
     * @return void
     */
    public function testPropertyTypeInDocblockIsResolved(): void
    {
        $property = $this->indexProperty('PropertyTypeInDocblockIsResolved.phpt');

        self::assertSame('\N\A', (string) $property->getType());
    }

    /**
     * @return void
     */
    public function testPublicProperty(): void
    {
        $property = $this->indexProperty('PublicProperty.phpt');

        self::assertSame(AccessModifierNameValue::PUBLIC_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testProtectedProperty(): void
    {
        $property = $this->indexProperty('ProtectedProperty.phpt');

        self::assertSame(AccessModifierNameValue::PROTECTED_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testPrivateProperty(): void
    {
        $property = $this->indexProperty('PrivateProperty.phpt');

        self::assertSame(AccessModifierNameValue::PRIVATE_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testImplicitlyPublicProperty(): void
    {
        $property = $this->indexProperty('ImplicitlyPublicProperty.phpt');

        self::assertSame(AccessModifierNameValue::PUBLIC_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testCompoundProperty(): void
    {
        $path = $this->getPathFor('CompoundProperty.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        self::assertCount(1, $classes);
        self::assertCount(2, $classes[0]->getProperties());

        self::assertSame('foo', $classes[0]->getProperties()[0]->getName());
        self::assertSame('bar', $classes[0]->getProperties()[1]->getName());
    }

    /**
     * @return void
     */
    public function testCompoundPropertyPropagatesAccessModifierToAllProperties(): void
    {
        $path = $this->getPathFor('CompoundPropertyPropagatesAccessModifierToAllProperties.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        self::assertCount(1, $classes);
        self::assertCount(2, $classes[0]->getProperties());

        self::assertSame(AccessModifierNameValue::PROTECTED_, $classes[0]->getProperties()[0]->getAccessModifier()->getName());
        self::assertSame(AccessModifierNameValue::PROTECTED_, $classes[0]->getProperties()[1]->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testCompoundPropertyPropagatesLongDescriptionToAllProperties(): void
    {
        $path = $this->getPathFor('CompoundPropertyPropagatesDescriptionsToAllProperties.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        self::assertCount(1, $classes);
        self::assertCount(2, $classes[0]->getProperties());

        self::assertSame('A summary.', $classes[0]->getProperties()[0]->getShortDescription());
        self::assertSame('A long description.', $classes[0]->getProperties()[0]->getLongDescription());
        self::assertSame('A summary.', $classes[0]->getProperties()[1]->getShortDescription());
        self::assertSame('A long description.', $classes[0]->getProperties()[1]->getLongDescription());
    }

    /**
     * @return void
     */
    public function testCompoundPropertyDistinguishesTypesFromDocblockBasedOnName(): void
    {
        $path = $this->getPathFor('CompoundPropertyDistinguishesTypesFromDocblockBasedOnName.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        self::assertCount(1, $classes);
        self::assertCount(2, $classes[0]->getProperties());

        self::assertSame('string', (string) $classes[0]->getProperties()[0]->getType());
        self::assertSame('First description.', $classes[0]->getProperties()[0]->getTypeDescription());
        self::assertSame('First description.', $classes[0]->getProperties()[0]->getShortDescription());

        self::assertSame('int', (string) $classes[0]->getProperties()[1]->getType());
        self::assertSame('Second description.', $classes[0]->getProperties()[1]->getTypeDescription());
        self::assertSame('Second description.', $classes[0]->getProperties()[1]->getShortDescription());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(1, $classes);
            self::assertCount(1, $classes[0]->getProperties());

            $property = $classes[0]->getProperties()[0];

            self::assertSame('foo', $property->getName());

            return str_replace('foo', 'foo2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(1, $classes);
            self::assertCount(1, $classes[0]->getProperties());

            $property = $classes[0]->getProperties()[0];

            self::assertSame('foo2', $property->getName());
        };

        $path = $this->getPathFor('PropertyChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Property
     */
    private function indexProperty(string $file): Structures\Property
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        self::assertCount(1, $classes);
        self::assertCount(1, $classes[0]->getProperties());

        $property = $classes[0]->getProperties()[0];

        self::assertSame($classes[0], $property->getClasslike());

        return $property;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/PropertyIndexingTest/' . $file;
    }
}
