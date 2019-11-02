<?php

namespace Serenata\Tests\Integration\Tooltips;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Indexing\Structures\AccessModifierNameValue;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ClassConstantIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleConstant(): void
    {
        $constant = $this->indexConstant('SimpleConstant.phpt');

        static::assertSame('CONSTANT', $constant->getName());
        static::assertSame($this->normalizePath($this->getPathFor('SimpleConstant.phpt')), $constant->getFile()->getUri());
        static::assertEquals(
            new Range(
                new Position(4, 10),
                new Position(4, 27)
            ),
            $constant->getRange()
        );
        static::assertSame("'test'", $constant->getDefaultValue());
        static::assertFalse($constant->getIsDeprecated());
        static::assertFalse($constant->getHasDocblock());
        static::assertNull($constant->getShortDescription());
        static::assertNull($constant->getLongDescription());
        static::assertNull($constant->getTypeDescription());
        static::assertSame('string', $constant->getType()->toString());
        static::assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testClassKeywordConstant(): void
    {
        $path = $this->getPathFor('ClassKeywordConstant.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        static::assertCount(1, $classes);
        static::assertCount(1, $classes[0]->getConstants());

        $constant = $classes[0]->getConstants()[0];

        static::assertSame($classes[0], $constant->getClasslike());

        static::assertSame('class', $constant->getName());
        static::assertSame($this->normalizePath($this->getPathFor('ClassKeywordConstant.phpt')), $constant->getFile()->getUri());
        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 0)
            ),
            $constant->getRange()
        );
        static::assertSame("'Test'", $constant->getDefaultValue());
        static::assertFalse($constant->getIsDeprecated());
        static::assertFalse($constant->getHasDocblock());
        static::assertSame('PHP built-in class constant that evaluates to the FQCN.', $constant->getShortDescription());
        static::assertNull($constant->getLongDescription());
        static::assertNull($constant->getTypeDescription());
        static::assertSame('string', $constant->getType()->toString());
        static::assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testDeprecatedConstant(): void
    {
        $constant = $this->indexConstant('DeprecatedConstant.phpt');

        static::assertTrue($constant->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testConstantShortDescription(): void
    {
        $constant = $this->indexConstant('ConstantShortDescription.phpt');

        static::assertSame('This is a summary.', $constant->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantLongDescription(): void
    {
        $constant = $this->indexConstant('ConstantLongDescription.phpt');

        static::assertSame('This is a long description.', $constant->getLongDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescription(): void
    {
        $constant = $this->indexConstant('ConstantTypeDescription.phpt');

        static::assertSame('This is a type description.', $constant->getTypeDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescriptionIsUsedAsSummaryIfSummaryIsMissing(): void
    {
        $constant = $this->indexConstant('ConstantTypeDescriptionAsSummary.phpt');

        static::assertSame('This is a type description.', $constant->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescriptionTakesPrecedenceOverSummary(): void
    {
        $property = $this->indexConstant('ConstantTypeDescriptionTakesPrecedenceOverSummary.phpt');

        static::assertSame('This is a type description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeIsFetchedFromDocblockAndGetsPrecedenceOverDefaultValueType(): void
    {
        $constant = $this->indexConstant('ConstantTypeFromDocblock.phpt');

        static::assertSame('int', $constant->getType()->toString());
    }

    /**
     * @return void
     */
    public function testConstantTypeInDocblockIsResolved(): void
    {
        $constant = $this->indexConstant('ConstantTypeInDocblockIsResolved.phpt');

        static::assertSame('\N\A', $constant->getType()->toString());
    }

    /**
     * @return void
     */
    public function testImplicitlyPublicConstant(): void
    {
        $constant = $this->indexConstant('ImplicitlyPublicConstant.phpt');

        static::assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testExplicitlyPublicConstant(): void
    {
        $constant = $this->indexConstant('ExplicitlyPublicConstant.phpt');

        static::assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testProtectedConstant(): void
    {
        $constant = $this->indexConstant('ProtectedConstant.phpt');

        static::assertSame(AccessModifierNameValue::PROTECTED_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testPrivateConstant(): void
    {
        $constant = $this->indexConstant('PrivateConstant.phpt');

        static::assertSame(AccessModifierNameValue::PRIVATE_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testConstantWithoutTypeSpecificationGetsMixedType(): void
    {
        $constant = $this->indexConstant('ConstantNoTypeSpecification.phpt');

        static::assertSame('mixed', $constant->getType()->toString());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(1, $classes);
            static::assertCount(2, $classes[0]->getConstants());

            $constant = $classes[0]->getConstants()[1];

            static::assertSame('CONSTANT', $constant->getName());

            return str_replace('CONSTANT', 'CONSTANT2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(1, $classes);
            static::assertCount(2, $classes[0]->getConstants());

            $constant = $classes[0]->getConstants()[1];

            static::assertSame('CONSTANT2', $constant->getName());
        };

        $path = $this->getPathFor('ConstantChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\ClassConstant
     */
    private function indexConstant(string $file): Structures\ClassConstant
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        static::assertCount(1, $classes);
        static::assertCount(2, $classes[0]->getConstants());

        $constant = $classes[0]->getConstants()[1];

        static::assertSame($classes[0], $constant->getClasslike());

        return $constant;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/ClassConstantIndexingTest/' . $file;
    }
}
