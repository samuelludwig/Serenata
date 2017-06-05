<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\Structures;

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

        $this->assertEquals('CONSTANT', $constant->getName());
        $this->assertEquals($this->getPathFor('SimpleConstant.phpt'), $constant->getFile()->getPath());
        $this->assertEquals(5, $constant->getStartLine());
        $this->assertEquals(5, $constant->getEndLine());
        $this->assertEquals("'test'", $constant->getDefaultValue());
        $this->assertFalse($constant->getIsDeprecated());
        $this->assertFalse($constant->getHasDocblock());
        $this->assertNull($constant->getShortDescription());
        $this->assertNull($constant->getLongDescription());
        $this->assertNull($constant->getTypeDescription());
        $this->assertCount(1, $constant->getTypes());
        $this->assertEquals('string', $constant->getTypes()[0]->getType());
        $this->assertEquals('string', $constant->getTypes()[0]->getFqcn());
        $this->assertEquals('string', $constant->getTypes()[0]->getFqcn());
        $this->assertEquals('public', $constant->getAccessModifier()->getName());
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

        $this->assertEquals('This is a summary.', $constant->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantLongDescription(): void
    {
        $constant = $this->indexConstant('ConstantLongDescription.phpt');

        $this->assertEquals('This is a long description.', $constant->getLongDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescription(): void
    {
        $constant = $this->indexConstant('ConstantTypeDescription.phpt');

        $this->assertEquals('This is a type description.', $constant->getTypeDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeDescriptionIsUsedAsSummaryIfSummaryIsMissing(): void
    {
        $constant = $this->indexConstant('ConstantTypeDescriptionAsSummary.phpt');

        $this->assertEquals('This is a type description.', $constant->getShortDescription());
    }

    /**
     * @return void
     */
    public function testConstantTypeIsFetchedFromDocblockAndGetsPrecedenceOverDefaultValueType(): void
    {
        $constant = $this->indexConstant('ConstantTypeFromDocblock.phpt');

        $this->assertEquals('int', $constant->getTypes()[0]->getType());
        $this->assertEquals('int', $constant->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testConstantTypeInDocblockIsResolved(): void
    {
        $constant = $this->indexConstant('ConstantTypeInDocblockIsResolved.phpt');

        $this->assertEquals('A', $constant->getTypes()[0]->getType());
        $this->assertEquals('\N\A', $constant->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testImplicitlyPublicConstant(): void
    {
        $constant = $this->indexConstant('ImplicitlyPublicConstant.phpt');

        $this->assertEquals('public', $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testExplicitlyPublicConstant(): void
    {
        $constant = $this->indexConstant('ExplicitlyPublicConstant.phpt');

        $this->assertEquals('public', $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testProtectedConstant(): void
    {
        $constant = $this->indexConstant('ProtectedConstant.phpt');

        $this->assertEquals('protected', $constant->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testPrivateConstant(): void
    {
        $constant = $this->indexConstant('PrivateConstant.phpt');

        $this->assertEquals('private', $constant->getAccessModifier()->getName());
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

            $this->assertEquals('CONSTANT', $constant->getName());

            return str_replace('CONSTANT', 'CONSTANT2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            $this->assertCount(1, $classes);
            $this->assertCount(2, $classes[0]->getConstants());

            $constant = $classes[0]->getConstants()[1];

            $this->assertEquals('CONSTANT2', $constant->getName());
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

        $this->assertEquals($classes[0], $constant->getStructure());

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
