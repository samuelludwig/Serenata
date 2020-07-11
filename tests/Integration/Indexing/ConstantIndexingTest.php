<?php

namespace Serenata\Tests\Integration\Indexing;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ConstantIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleConstant(): void
    {
        $constant = $this->indexConstant('SimpleConstant.phpt');

        self::assertSame('CONSTANT', $constant->getName());
        self::assertSame('\CONSTANT', $constant->getFqcn());
        self::assertSame($this->normalizePath($this->getPathFor('SimpleConstant.phpt')), $constant->getFile()->getUri());
        self::assertEquals(
            new Range(
                new Position(2, 6),
                new Position(2, 23)
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
    public function testConstantFqcnIsInCurrentNamespace(): void
    {
        $constant = $this->indexConstant('ConstantFqcnInNamespace.phpt');

        self::assertSame('\A\CONSTANT', $constant->getFqcn());
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
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $constants = $this->container->get('managerRegistry')->getRepository(Structures\Constant::class)->findAll();

            self::assertCount(1, $constants);
            self::assertSame('\CONSTANT', $constants[0]->getFqcn());

            return str_replace('CONSTANT', 'CONSTANT2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $constants = $this->container->get('managerRegistry')->getRepository(Structures\Constant::class)->findAll();

            self::assertCount(1, $constants);
            self::assertSame('\CONSTANT2', $constants[0]->getFqcn());
        };

        $path = $this->getPathFor('ConstantChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Constant
     */
    private function indexConstant(string $file): Structures\Constant
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $constants = $this->container->get('managerRegistry')->getRepository(Structures\Constant::class)->findAll();

        self::assertCount(1, $constants);

        return $constants[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/ConstantIndexingTest/' . $file;
    }
}
