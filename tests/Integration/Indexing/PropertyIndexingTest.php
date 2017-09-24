<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\Structures;

use PhpIntegrator\Indexing\Structures\AccessModifierNameValue;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class PropertyIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleProperty(): void
    {
        $property = $this->indexProperty('SimpleProperty.phpt');

        $this->assertSame('foo', $property->getName());
        $this->assertSame($this->getPathFor('SimpleProperty.phpt'), $property->getFile()->getPath());
        $this->assertSame(5, $property->getStartLine());
        $this->assertSame(5, $property->getEndLine());
        $this->assertSame("'test'", $property->getDefaultValue());
        $this->assertFalse($property->getIsDeprecated());
        $this->assertFalse($property->getIsMagic());
        $this->assertFalse($property->getIsStatic());
        $this->assertFalse($property->getHasDocblock());
        $this->assertNull($property->getShortDescription());
        $this->assertNull($property->getLongDescription());
        $this->assertNull($property->getTypeDescription());
        $this->assertCount(1, $property->getTypes());
        $this->assertSame('string', $property->getTypes()[0]->getType());
        $this->assertSame('string', $property->getTypes()[0]->getFqcn());
        $this->assertSame(AccessModifierNameValue::PUBLIC_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testDeprecatedProperty(): void
    {
        $property = $this->indexProperty('DeprecatedProperty.phpt');

        $this->assertTrue($property->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testStaticProperty(): void
    {
        $property = $this->indexProperty('StaticProperty.phpt');

        $this->assertTrue($property->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicProperty(): void
    {
        $property = $this->indexProperty('MagicProperty.phpt');

        $this->assertTrue($property->getIsMagic());
    }

    /**
     * @return void
     */
    public function testMagicStaticProperty(): void
    {
        $property = $this->indexProperty('MagicStaticProperty.phpt');

        $this->assertTrue($property->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicPropertyWithDescription(): void
    {
        $property = $this->indexProperty('MagicPropertyWithDescription.phpt');

        $this->assertSame('A description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testMagicPropertyTypeResolution(): void
    {
        $property = $this->indexProperty('MagicPropertyTypeResolution.phpt');

        $this->assertSame('A', $property->getTypes()[0]->getType());
        $this->assertSame('\N\A', $property->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testMagicReadProperty(): void
    {
        $property = $this->indexProperty('MagicReadProperty.phpt');

        $this->assertTrue($property->getIsMagic());
    }

    /**
     * @return void
     */
    public function testMagicWriteProperty(): void
    {
        $property = $this->indexProperty('MagicWriteProperty.phpt');

        $this->assertTrue($property->getIsMagic());
    }

    /**
     * @return void
     */
    public function testPropertyShortDescription(): void
    {
        $property = $this->indexProperty('PropertyShortDescription.phpt');

        $this->assertSame('This is a summary.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testPropertyLongDescription(): void
    {
        $property = $this->indexProperty('PropertyLongDescription.phpt');

        $this->assertSame('This is a long description.', $property->getLongDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeDescription(): void
    {
        $property = $this->indexProperty('PropertyTypeDescription.phpt');

        $this->assertSame('This is a type description.', $property->getTypeDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeDescriptionIsUsedAsSummaryIfSummaryIsMissing(): void
    {
        $property = $this->indexProperty('PropertyTypeDescriptionAsSummary.phpt');

        $this->assertSame('This is a type description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeDescriptionTakesPrecedenceOverSummary(): void
    {
        $property = $this->indexProperty('PropertyTypeDescriptionTakesPrecedenceOverSummary.phpt');

        $this->assertSame('This is a type description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testPropertyTypeIsFetchedFromDocblockAndGetsPrecedenceOverDefaultValueType(): void
    {
        $property = $this->indexProperty('PropertyTypeFromDocblock.phpt');

        $this->assertSame('int', $property->getTypes()[0]->getType());
        $this->assertSame('int', $property->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testPropertyTypeInDocblockIsResolved(): void
    {
        $property = $this->indexProperty('PropertyTypeInDocblockIsResolved.phpt');

        $this->assertSame('A', $property->getTypes()[0]->getType());
        $this->assertSame('\N\A', $property->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testPublicProperty(): void
    {
        $property = $this->indexProperty('PublicProperty.phpt');

        $this->assertSame(AccessModifierNameValue::PUBLIC_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testProtectedProperty(): void
    {
        $property = $this->indexProperty('ProtectedProperty.phpt');

        $this->assertSame(AccessModifierNameValue::PROTECTED_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testPrivateProperty(): void
    {
        $property = $this->indexProperty('PrivateProperty.phpt');

        $this->assertSame(AccessModifierNameValue::PRIVATE_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testImplicitlyPublicProperty(): void
    {
        $property = $this->indexProperty('ImplicitlyPublicProperty.phpt');

        $this->assertSame(AccessModifierNameValue::PUBLIC_, $property->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testCompoundProperty(): void
    {
        $path = $this->getPathFor('CompoundProperty.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        $this->assertCount(1, $classes);
        $this->assertCount(2, $classes[0]->getProperties());

        $this->assertSame('foo', $classes[0]->getProperties()[0]->getName());
        $this->assertSame('bar', $classes[0]->getProperties()[1]->getName());
    }

    /**
     * @return void
     */
    public function testCompoundPropertyPropagatesAccessModifierToAllProperties(): void
    {
        $path = $this->getPathFor('CompoundPropertyPropagatesAccessModifierToAllProperties.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        $this->assertCount(1, $classes);
        $this->assertCount(2, $classes[0]->getProperties());

        $this->assertSame(AccessModifierNameValue::PROTECTED_, $classes[0]->getProperties()[0]->getAccessModifier()->getName());
        $this->assertSame(AccessModifierNameValue::PROTECTED_, $classes[0]->getProperties()[1]->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testCompoundPropertyPropagatesLongDescriptionToAllProperties(): void
    {
        $path = $this->getPathFor('CompoundPropertyPropagatesDescriptionsToAllProperties.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        $this->assertCount(1, $classes);
        $this->assertCount(2, $classes[0]->getProperties());

        $this->assertSame('A summary.', $classes[0]->getProperties()[0]->getShortDescription());
        $this->assertSame('A long description.', $classes[0]->getProperties()[0]->getLongDescription());
        $this->assertSame('A summary.', $classes[0]->getProperties()[1]->getShortDescription());
        $this->assertSame('A long description.', $classes[0]->getProperties()[1]->getLongDescription());
    }

    /**
     * @return void
     */
    public function testCompoundPropertyDistinguishesTypesFromDocblockBasedOnName(): void
    {
        $path = $this->getPathFor('CompoundPropertyDistinguishesTypesFromDocblockBasedOnName.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        $this->assertCount(1, $classes);
        $this->assertCount(2, $classes[0]->getProperties());

        $this->assertCount(1, $classes[0]->getProperties()[0]->getTypes());
        $this->assertSame('string', $classes[0]->getProperties()[0]->getTypes()[0]->getType());
        $this->assertSame('First description.', $classes[0]->getProperties()[0]->getTypeDescription());
        $this->assertSame('First description.', $classes[0]->getProperties()[0]->getShortDescription());

        $this->assertCount(1, $classes[0]->getProperties()[1]->getTypes());
        $this->assertSame('int', $classes[0]->getProperties()[1]->getTypes()[0]->getType());
        $this->assertSame('Second description.', $classes[0]->getProperties()[1]->getTypeDescription());
        $this->assertSame('Second description.', $classes[0]->getProperties()[1]->getShortDescription());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            $this->assertCount(1, $classes);
            $this->assertCount(1, $classes[0]->getProperties());

            $property = $classes[0]->getProperties()[0];

            $this->assertSame('foo', $property->getName());

            return str_replace('foo', 'foo2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            $this->assertCount(1, $classes);
            $this->assertCount(1, $classes[0]->getProperties());

            $property = $classes[0]->getProperties()[0];

            $this->assertSame('foo2', $property->getName());
        };

        $path = $this->getPathFor('PropertyChanges.phpt');

        $this->assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Property
     */
    protected function indexProperty(string $file): Structures\Property
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        $this->assertCount(1, $classes);
        $this->assertCount(1, $classes[0]->getProperties());

        $property = $classes[0]->getProperties()[0];

        $this->assertSame($classes[0], $property->getStructure());

        return $property;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/PropertyIndexingTest/' . $file;
    }
}
