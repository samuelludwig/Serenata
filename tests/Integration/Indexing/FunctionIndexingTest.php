<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\Structures;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class FunctionIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleFunction(): void
    {
        $function = $this->indexFunction('SimpleFunction.phpt');

        $this->assertEquals('foo', $function->getName());
        $this->assertEquals('\foo', $function->getFqcn());
        $this->assertEquals($this->getPathFor('SimpleFunction.phpt'), $function->getFile()->getPath());
        $this->assertEquals(3, $function->getStartLine());
        $this->assertEquals(6, $function->getEndLine());
        $this->assertFalse($function->getIsDeprecated());
        $this->assertNull($function->getShortDescription());
        $this->assertNull($function->getLongDescription());
        $this->assertNull($function->getReturnDescription());
        $this->assertNull($function->getReturnTypeHint());
        $this->assertFalse($function->getHasDocblock());
        $this->assertEmpty($function->getThrows());
        $this->assertEmpty($function->getParameters());
        $this->assertEmpty($function->getReturnTypes());
    }

    /**
     * @return void
     */
    public function testDeprecatedFunction(): void
    {
        $function = $this->indexFunction('DeprecatedFunction.phpt');

        $this->assertTrue($function->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testFunctionShortDescription(): void
    {
        $function = $this->indexFunction('FunctionShortDescription.phpt');

        $this->assertEquals('This is a summary.', $function->getShortDescription());
    }

    /**
     * @return void
     */
    public function testFunctionLongDescription(): void
    {
        $function = $this->indexFunction('FunctionLongDescription.phpt');

        $this->assertEquals('This is a long description.', $function->getLongDescription());
    }

    /**
     * @return void
     */
    public function testFunctionReturnDescription(): void
    {
        $function = $this->indexFunction('FunctionReturnDescription.phpt');

        $this->assertEquals('This is a return description.', $function->getReturnDescription());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeIsFetchedFromDocblockAndGetsPrecedenceOverReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeFromDocblock.phpt');

        $this->assertEquals('int', $function->getReturnTypes()[0]->getType());
        $this->assertEquals('int', $function->getReturnTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeIsFetchedFromReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeFromTypeHint.phpt');

        $this->assertEquals('string', $function->getReturnTypes()[0]->getType());
        $this->assertEquals('string', $function->getReturnTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeHint.phpt');

        $this->assertEquals('string', $function->getReturnTypeHint());
    }

    /**
     * @return void
     */
    public function testFunctionFqcnIsInCurrentNamespace(): void
    {
        $function = $this->indexFunction('FunctionFqcnInNamespace.phpt');

        $this->assertEquals('\A\foo', $function->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeInDocblockIsResolved(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeInDocblockIsResolved.phpt');

        $this->assertEquals('A', $function->getReturnTypes()[0]->getType());
        $this->assertEquals('\N\A', $function->getReturnTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeInReturnTypeHintIsResolved(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeInReturnTypeHintIsResolved.phpt');

        $this->assertEquals('A', $function->getReturnTypes()[0]->getType());
        $this->assertEquals('\N\A', $function->getReturnTypes()[0]->getFqcn());
        $this->assertEquals('\N\A', $function->getReturnTypeHint());
    }

    /**
     * @return void
     */
    public function testFunctionThrows(): void
    {
        $function = $this->indexFunction('FunctionThrows.phpt');

        $this->assertCount(2, $function->getThrows());

        $this->assertEquals('A', $function->getThrows()[0]['type']);
        $this->assertEquals('\N\A', $function->getThrows()[0]['full_type']);
        $this->assertNull($function->getThrows()[0]['description']);

        $this->assertEquals('\Exception', $function->getThrows()[1]['type']);
        $this->assertEquals('\Exception', $function->getThrows()[1]['full_type']);
        $this->assertEquals('when something goes wrong.', $function->getThrows()[1]['description']);
    }

    // TODO: Test parameter type resolving in type hint
    // TODO: Test parameter type resolving in docblock
    // TODO: Test parameters with type hints
    // TODO: Test parameters with default values
    // TODO: Test parameters with explicit nullability
    // TODO: Test parameters with implicit nullability
    // TODO: Test reference parameters
    // TODO: Test variadic parameters
    // TODO: Test docblock parameter type > parameter type hint

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $functions = $this->container->get('managerRegistry')->getRepository(Structures\Function_::class)->findAll();

            $this->assertCount(1, $functions);
            $this->assertEquals('\foo', $functions[0]->getFqcn());

            return str_replace('foo', 'foo2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $functions = $this->container->get('managerRegistry')->getRepository(Structures\Function_::class)->findAll();

            $this->assertCount(1, $functions);
            $this->assertEquals('\foo2', $functions[0]->getFqcn());
        };

        $path = $this->getPathFor('FunctionChanges.phpt');

        $this->assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Function_
     */
    protected function indexFunction(string $file): Structures\Function_
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $functions = $this->container->get('managerRegistry')->getRepository(Structures\Function_::class)->findAll();

        $this->assertCount(1, $functions);

        return $functions[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/FunctionIndexingTest/' . $file;
    }
}
