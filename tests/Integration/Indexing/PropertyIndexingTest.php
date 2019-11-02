<?php

namespace Serenata\Tests\Integration\Tooltips;

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

        static::assertSame('foo', $property->getName());
        static::assertSame($this->normalizePath($this->getPathFor('SimpleProperty.phpt')), $property->getFile()->getUri());
        static::assertEquals(
            new Range(
                new Position(4, 4),
                new Position(4, 24)
            ),
            $property->getRange()
        );
        static::assertSame("'test'", $property->getDefaultValue());
        static::assertFalse($property->getIsDeprecated());
        static::assertFalse($property->getIsMagic());
        static::assertFalse($property->getIsStatic());
        static::assertFalse($property->getHasDocblock());
        static::assertNull($property->getShortDescription());
        static::assertNull($property->getLongDescription());
        static::assertNull($property->getTypeDescription());
        static::assertSame('string', $property->getType()->toString());
        static::assertSame(AccessModifierNameValue::PUBLIC_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testDeprecatedProperty(): void
    {
        $property = $this->indexProperty('DeprecatedProperty.phpt');

        static::assertTrue($property->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testStaticProperty(): void
    {
        $property = $this->indexProperty('StaticProperty.phpt');

        static::assertTrue($property->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicProperty(): void
    {
        $property = $this->indexProperty('MagicProperty.phpt');

        static::assertTrue($property->getIsMagic());
    }

    /**
     * @return void
     */
    public function testMagicStaticProperty(): void
    {
        $property = $this->indexProperty('MagicStaticProperty.phpt');

        static::assertTrue($property->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicPropertyWithDescription(): void
    {
        $property = $this->indexProperty('MagicPropertyWithDescription.phpt');

        static::assertSame('A description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testMagicPropertyTypeResolution(): void
    {
        $property = $this->indexProperty('MagicPropertyTypeResolution.phpt');

        static::assertSame('\N\A', $property->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMagicReadProperty(): void
    {
        $property = $this->indexProperty('MagicReadProperty.phpt');

        static::assertTrue($property->getIsMagic());
    }

    /**
     * @return void
     */
    public function testMagicWriteProperty(): void
    {
        $property = $this->indexProperty('MagicWriteProperty.phpt');

        static::assertTrue($property->getIsMagic());
    }

    /**
     * @return void
     */
    public function testPropertyShortDescription(): void
    {
        $property = $this->indexProperty('PropertyShortDescription.phpt');

        static::assertSame('This is a summary.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testPropertyLongDescription(): void
    {
        $property = $this->indexProperty('PropertyLongDescription.phpt');

        static::assertSame('This is a long description.', $property->getLongDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeDescription(): void
    {
        $property = $this->indexProperty('PropertyTypeDescription.phpt');

        static::assertSame('This is a type description.', $property->getTypeDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeDescriptionIsUsedAsSummaryIfSummaryIsMissing(): void
    {
        $property = $this->indexProperty('PropertyTypeDescriptionAsSummary.phpt');

        static::assertSame('This is a type description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeDescriptionTakesPrecedenceOverSummary(): void
    {
        $property = $this->indexProperty('PropertyTypeDescriptionTakesPrecedenceOverSummary.phpt');

        static::assertSame('This is a type description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeIsFetchedFromDocblockAndGetsPrecedenceOverDefaultValueType(): void
    {
        $property = $this->indexProperty('PropertyTypeFromDocblock.phpt');

        static::assertSame('int', $property->getType()->toString());
    }

    /**
     * @return void
     */
    public function testPropertyTypeIsFetchedFromDefinitionAndGetsPrecedenceOverDefaultValueType(): void
    {
        $property = $this->indexProperty('TypedProperty.phpt');

        static::assertSame('string|null', $property->getType()->toString());
    }

    /**
     * @return void
     */
    public function testPropertyTypeInDocblockGetsPrecedenceOverTypeInDefinition(): void
    {
        $property = $this->indexProperty('TypedPropertyWithDocblockOverride.phpt');

        static::assertSame('\DateTime', $property->getType()->toString());
    }

    /**
     * @return void
     */
    public function testPropertyTypeInDocblockIsResolved(): void
    {
        $property = $this->indexProperty('PropertyTypeInDocblockIsResolved.phpt');

        static::assertSame('\N\A', $property->getType()->toString());
    }

    /**
     * @return void
     */
    public function testPublicProperty(): void
    {
        $property = $this->indexProperty('PublicProperty.phpt');

        static::assertSame(AccessModifierNameValue::PUBLIC_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testProtectedProperty(): void
    {
        $property = $this->indexProperty('ProtectedProperty.phpt');

        static::assertSame(AccessModifierNameValue::PROTECTED_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testPrivateProperty(): void
    {
        $property = $this->indexProperty('PrivateProperty.phpt');

        static::assertSame(AccessModifierNameValue::PRIVATE_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testImplicitlyPublicProperty(): void
    {
        $property = $this->indexProperty('ImplicitlyPublicProperty.phpt');

        static::assertSame(AccessModifierNameValue::PUBLIC_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testCompoundProperty(): void
    {
        $path = $this->getPathFor('CompoundProperty.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        static::assertCount(1, $classes);
        static::assertCount(2, $classes[0]->getProperties());

        static::assertSame('foo', $classes[0]->getProperties()[0]->getName());
        static::assertSame('bar', $classes[0]->getProperties()[1]->getName());
    }

    /**
     * @return void
     */
    public function testCompoundPropertyPropagatesAccessModifierToAllProperties(): void
    {
        $path = $this->getPathFor('CompoundPropertyPropagatesAccessModifierToAllProperties.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        static::assertCount(1, $classes);
        static::assertCount(2, $classes[0]->getProperties());

        static::assertSame(AccessModifierNameValue::PROTECTED_, $classes[0]->getProperties()[0]->getAccessModifier()->getName());
        static::assertSame(AccessModifierNameValue::PROTECTED_, $classes[0]->getProperties()[1]->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testCompoundPropertyPropagatesLongDescriptionToAllProperties(): void
    {
        $path = $this->getPathFor('CompoundPropertyPropagatesDescriptionsToAllProperties.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        static::assertCount(1, $classes);
        static::assertCount(2, $classes[0]->getProperties());

        static::assertSame('A summary.', $classes[0]->getProperties()[0]->getShortDescription());
        static::assertSame('A long description.', $classes[0]->getProperties()[0]->getLongDescription());
        static::assertSame('A summary.', $classes[0]->getProperties()[1]->getShortDescription());
        static::assertSame('A long description.', $classes[0]->getProperties()[1]->getLongDescription());
    }

    /**
     * @return void
     */
    public function testCompoundPropertyDistinguishesTypesFromDocblockBasedOnName(): void
    {
        $path = $this->getPathFor('CompoundPropertyDistinguishesTypesFromDocblockBasedOnName.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        static::assertCount(1, $classes);
        static::assertCount(2, $classes[0]->getProperties());

        static::assertSame('string', $classes[0]->getProperties()[0]->getType()->toString());
        static::assertSame('First description.', $classes[0]->getProperties()[0]->getTypeDescription());
        static::assertSame('First description.', $classes[0]->getProperties()[0]->getShortDescription());

        static::assertSame('int', $classes[0]->getProperties()[1]->getType()->toString());
        static::assertSame('Second description.', $classes[0]->getProperties()[1]->getTypeDescription());
        static::assertSame('Second description.', $classes[0]->getProperties()[1]->getShortDescription());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(1, $classes);
            static::assertCount(1, $classes[0]->getProperties());

            $property = $classes[0]->getProperties()[0];

            static::assertSame('foo', $property->getName());

            return str_replace('foo', 'foo2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(1, $classes);
            static::assertCount(1, $classes[0]->getProperties());

            $property = $classes[0]->getProperties()[0];

            static::assertSame('foo2', $property->getName());
        };

        $path = $this->getPathFor('PropertyChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
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

        static::assertCount(1, $classes);
        static::assertCount(1, $classes[0]->getProperties());

        $property = $classes[0]->getProperties()[0];

        static::assertSame($classes[0], $property->getClasslike());

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
