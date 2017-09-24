<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\Structures;

use PhpIntegrator\Indexing\Structures\AccessModifierNameValue;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClassConstantIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleConstant(): void
    {
        $constant = $this->indexConstant('SimpleConstant.phpt');

        $this->assertSame('CONSTANT', $constant->getName());
        $this->assertSame($this->getPathFor('SimpleConstant.phpt'), $constant->getFile()->getPath());
        $this->assertSame(5, $constant->getStartLine());
        $this->assertSame(5, $constant->getEndLine());
        $this->assertSame("'test'", $constant->getDefaultValue());
        $this->assertFalse($constant->getIsDeprecated());
        $this->assertFalse($constant->getHasDocblock());
        $this->assertNull($constant->getShortDescription());
        $this->assertNull($constant->getLongDescription());
        $this->assertNull($constant->getTypeDescription());
        $this->assertCount(1, $constant->getTypes());
        $this->assertSame('string', $constant->getTypes()[0]->getType());
        $this->assertSame('string', $constant->getTypes()[0]->getFqcn());
        $this->assertSame('string', $constant->getTypes()[0]->getFqcn());
        $this->assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testClassKeywordConstant(): void
    {
        $path = $this->getPathFor('ClassKeywordConstant.phpt');

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        $this->assertCount(1, $classes);
        $this->assertCount(1, $classes[0]->getConstants());

        $constant = $classes[0]->getConstants()[0];

        $this->assertSame($classes[0], $constant->getStructure());

        $this->assertSame('class', $constant->getName());
        $this->assertSame($this->getPathFor('ClassKeywordConstant.phpt'), $constant->getFile()->getPath());
        $this->assertSame(3, $constant->getStartLine());
        $this->assertSame(3, $constant->getEndLine());
        $this->assertSame("'Test'", $constant->getDefaultValue());
        $this->assertFalse($constant->getIsDeprecated());
        $this->assertFalse($constant->getHasDocblock());
        $this->assertSame('PHP built-in class constant that evaluates to the FQCN.', $constant->getShortDescription());
        $this->assertNull($constant->getLongDescription());
        $this->assertNull($constant->getTypeDescription());
        $this->assertCount(1, $constant->getTypes());
        $this->assertSame('string', $constant->getTypes()[0]->getType());
        $this->assertSame('string', $constant->getTypes()[0]->getFqcn());
        $this->assertSame('string', $constant->getTypes()[0]->getFqcn());
        $this->assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testDeprecatedConstant(): void
    {
        $constant = $this->indexConstant('DeprecatedConstant.phpt');

        $this->assertTrue($constant->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testConstantShortDescription(): void
    {
        $constant = $this->indexConstant('ConstantShortDescription.phpt');

        $this->assertSame('This is a summary.', $constant->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantLongDescription(): void
    {
        $constant = $this->indexConstant('ConstantLongDescription.phpt');

        $this->assertSame('This is a long description.', $constant->getLongDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescription(): void
    {
        $constant = $this->indexConstant('ConstantTypeDescription.phpt');

        $this->assertSame('This is a type description.', $constant->getTypeDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescriptionIsUsedAsSummaryIfSummaryIsMissing(): void
    {
        $constant = $this->indexConstant('ConstantTypeDescriptionAsSummary.phpt');

        $this->assertSame('This is a type description.', $constant->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescriptionTakesPrecedenceOverSummary(): void
    {
        $property = $this->indexConstant('ConstantTypeDescriptionTakesPrecedenceOverSummary.phpt');

        $this->assertSame('This is a type description.', $property->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeIsFetchedFromDocblockAndGetsPrecedenceOverDefaultValueType(): void
    {
        $constant = $this->indexConstant('ConstantTypeFromDocblock.phpt');

        $this->assertSame('int', $constant->getTypes()[0]->getType());
        $this->assertSame('int', $constant->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testConstantTypeInDocblockIsResolved(): void
    {
        $constant = $this->indexConstant('ConstantTypeInDocblockIsResolved.phpt');

        $this->assertSame('A', $constant->getTypes()[0]->getType());
        $this->assertSame('\N\A', $constant->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testImplicitlyPublicConstant(): void
    {
        $constant = $this->indexConstant('ImplicitlyPublicConstant.phpt');

        $this->assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testExplicitlyPublicConstant(): void
    {
        $constant = $this->indexConstant('ExplicitlyPublicConstant.phpt');

        $this->assertSame(AccessModifierNameValue::PUBLIC_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testProtectedConstant(): void
    {
        $constant = $this->indexConstant('ProtectedConstant.phpt');

        $this->assertSame(AccessModifierNameValue::PROTECTED_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testPrivateConstant(): void
    {
        $constant = $this->indexConstant('PrivateConstant.phpt');

        $this->assertSame(AccessModifierNameValue::PRIVATE_, $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            $this->assertCount(1, $classes);
            $this->assertCount(2, $classes[0]->getConstants());

            $constant = $classes[0]->getConstants()[1];

            $this->assertSame('CONSTANT', $constant->getName());

            return str_replace('CONSTANT', 'CONSTANT2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            $this->assertCount(1, $classes);
            $this->assertCount(2, $classes[0]->getConstants());

            $constant = $classes[0]->getConstants()[1];

            $this->assertSame('CONSTANT2', $constant->getName());
        };

        $path = $this->getPathFor('ConstantChanges.phpt');

        $this->assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\ClassConstant
     */
    protected function indexConstant(string $file): Structures\ClassConstant
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        $this->assertCount(1, $classes);
        $this->assertCount(2, $classes[0]->getConstants());

        $constant = $classes[0]->getConstants()[1];

        $this->assertSame($classes[0], $constant->getStructure());

        return $constant;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/ClassConstantIndexingTest/' . $file;
    }
}
