<?php

namespace Serenata\Tests\Integration\Indexing;

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

        self::assertSame('CONSTANT', $constant->getName());
        self::assertSame($this->normalizePath($this->getPathFor('SimpleConstant.phpt')), $constant->getFile()->getUri());
        self::assertEquals(
            new Range(
                new Position(4, 10),
                new Position(4, 27)
            ),
            $constant->getRange()
        );
        self::assertSame("'test'", $constant->getDefaultValue());
        self::assertFalse($constant->getIsDeprecated());
        self::assertFalse($constant->getHasDocblock());
        self::assertNull($constant->getShortDescription());
        self::assertNull($constant->getLongDescription());
        self::assertNull($constant->getTypeDescription());
        self::assertSame('string', (string) $constant->getType());
        self::assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testClassKeywordConstant(): void
    {
        $path = $this->getPathFor('ClassKeywordConstant.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        self::assertCount(1, $classes);
        self::assertCount(1, $classes[0]->getConstants());

        $constant = $classes[0]->getConstants()[0];

        self::assertSame($classes[0], $constant->getClasslike());

        self::assertSame('class', $constant->getName());
        self::assertSame($this->normalizePath($this->getPathFor('ClassKeywordConstant.phpt')), $constant->getFile()->getUri());
        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 0)
            ),
            $constant->getRange()
        );
        self::assertSame("'Test'", $constant->getDefaultValue());
        self::assertFalse($constant->getIsDeprecated());
        self::assertFalse($constant->getHasDocblock());
        self::assertSame('PHP built-in class constant that evaluates to the FQCN.', $constant->getShortDescription());
        self::assertNull($constant->getLongDescription());
        self::assertNull($constant->getTypeDescription());
        self::assertSame('string', (string) $constant->getType());
        self::assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testDeprecatedConstant(): void
    {
        $constant = $this->indexConstant('DeprecatedConstant.phpt');

        self::assertTrue($constant->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testConstantShortDescription(): void
    {
        $constant = $this->indexConstant('ConstantShortDescription.phpt');

        self::assertSame('This is a summary.', $constant->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantLongDescription(): void
    {
        $constant = $this->indexConstant('ConstantLongDescription.phpt');

        self::assertSame('This is a long description.', $constant->getLongDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescription(): void
    {
        $constant = $this->indexConstant('ConstantTypeDescription.phpt');

        self::assertSame('This is a type description.', $constant->getTypeDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescriptionIsUsedAsSummaryIfSummaryIsMissing(): void
    {
        $constant = $this->indexConstant('ConstantTypeDescriptionAsSummary.phpt');

        self::assertSame('This is a type description.', $constant->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescriptionTakesPrecedenceOverSummary(): void
    {
        $property = $this->indexConstant('ConstantTypeDescriptionTakesPrecedenceOverSummary.phpt');

        self::assertSame('This is a type description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeIsFetchedFromDocblockAndGetsPrecedenceOverDefaultValueType(): void
    {
        $constant = $this->indexConstant('ConstantTypeFromDocblock.phpt');

        self::assertSame('int', (string) $constant->getType());
    }

    /**
     * @return void
     */
    public function testConstantTypeInDocblockIsResolved(): void
    {
        $constant = $this->indexConstant('ConstantTypeInDocblockIsResolved.phpt');

        self::assertSame('\N\A', (string) $constant->getType());
    }

    /**
     * @return void
     */
    public function testImplicitlyPublicConstant(): void
    {
        $constant = $this->indexConstant('ImplicitlyPublicConstant.phpt');

        self::assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testExplicitlyPublicConstant(): void
    {
        $constant = $this->indexConstant('ExplicitlyPublicConstant.phpt');

        self::assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testProtectedConstant(): void
    {
        $constant = $this->indexConstant('ProtectedConstant.phpt');

        self::assertSame(AccessModifierNameValue::PROTECTED_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testPrivateConstant(): void
    {
        $constant = $this->indexConstant('PrivateConstant.phpt');

        self::assertSame(AccessModifierNameValue::PRIVATE_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testConstantWithoutTypeSpecificationGetsMixedType(): void
    {
        $constant = $this->indexConstant('ConstantNoTypeSpecification.phpt');

        self::assertSame('mixed', (string) $constant->getType());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(1, $classes);
            self::assertCount(2, $classes[0]->getConstants());

            $constant = $classes[0]->getConstants()[1];

            self::assertSame('CONSTANT', $constant->getName());

            return str_replace('CONSTANT', 'CONSTANT2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(1, $classes);
            self::assertCount(2, $classes[0]->getConstants());

            $constant = $classes[0]->getConstants()[1];

            self::assertSame('CONSTANT2', $constant->getName());
        };

        $path = $this->getPathFor('ConstantChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
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

        self::assertCount(1, $classes);
        self::assertCount(2, $classes[0]->getConstants());

        $constant = $classes[0]->getConstants()[1];

        self::assertSame($classes[0], $constant->getClasslike());

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
