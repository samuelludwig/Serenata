<?php

namespace Serenata\Tests\Integration\Tooltips;

use Serenata\Indexing\Structures;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class FunctionIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleFunction(): void
    {
        $function = $this->indexFunction('SimpleFunction.phpt');

        static::assertSame('foo', $function->getName());
        static::assertSame('\foo', $function->getFqcn());
        static::assertSame($this->getPathFor('SimpleFunction.phpt'), $function->getFile()->getPath());
        static::assertSame(3, $function->getStartLine());
        static::assertSame(6, $function->getEndLine());
        static::assertFalse($function->getIsDeprecated());
        static::assertNull($function->getShortDescription());
        static::assertNull($function->getLongDescription());
        static::assertNull($function->getReturnDescription());
        static::assertNull($function->getReturnTypeHint());
        static::assertFalse($function->getHasDocblock());
        static::assertEmpty($function->getThrows());
        static::assertEmpty($function->getParameters());
        static::assertEmpty($function->getReturnTypes());
    }

    /**
     * @return void
     */
    public function testDeprecatedFunction(): void
    {
        $function = $this->indexFunction('DeprecatedFunction.phpt');

        static::assertTrue($function->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testFunctionShortDescription(): void
    {
        $function = $this->indexFunction('FunctionShortDescription.phpt');

        static::assertSame('This is a summary.', $function->getShortDescription());
    }

    /**
     * @return void
     */
    public function testFunctionLongDescription(): void
    {
        $function = $this->indexFunction('FunctionLongDescription.phpt');

        static::assertSame('This is a long description.', $function->getLongDescription());
    }

    /**
     * @return void
     */
    public function testFunctionReturnDescription(): void
    {
        $function = $this->indexFunction('FunctionReturnDescription.phpt');

        static::assertSame('This is a return description.', $function->getReturnDescription());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeIsFetchedFromDocblockAndGetsPrecedenceOverReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeFromDocblock.phpt');

        static::assertSame('int', $function->getReturnTypes()[0]->getType());
        static::assertSame('int', $function->getReturnTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeIsFetchedFromReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeFromTypeHint.phpt');

        static::assertSame('string', $function->getReturnTypes()[0]->getType());
        static::assertSame('string', $function->getReturnTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeHint.phpt');

        static::assertSame('string', $function->getReturnTypeHint());
    }

    /**
     * @return void
     */
    public function testFunctionExplicitlyNullableReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionExplicitlyNullableReturnTypeHint.phpt');

        static::assertSame('?string', $function->getReturnTypeHint());
        static::assertCount(2, $function->getReturnTypes());
        static::assertSame('string', $function->getReturnTypes()[0]->getType());
        static::assertSame('string', $function->getReturnTypes()[0]->getFqcn());
        static::assertSame('null', $function->getReturnTypes()[1]->getType());
        static::assertSame('null', $function->getReturnTypes()[1]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionFqcnIsInCurrentNamespace(): void
    {
        $function = $this->indexFunction('FunctionFqcnInNamespace.phpt');

        static::assertSame('\A\foo', $function->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeInDocblockIsResolved(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeInDocblockIsResolved.phpt');

        static::assertSame('A', $function->getReturnTypes()[0]->getType());
        static::assertSame('\N\A', $function->getReturnTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeInReturnTypeHintIsResolved(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeInReturnTypeHintIsResolved.phpt');

        static::assertSame('A', $function->getReturnTypes()[0]->getType());
        static::assertSame('\N\A', $function->getReturnTypes()[0]->getFqcn());
        static::assertSame('\N\A', $function->getReturnTypeHint());
    }

    /**
     * @return void
     */
    public function testFunctionThrows(): void
    {
        $function = $this->indexFunction('FunctionThrows.phpt');

        static::assertCount(2, $function->getThrows());

        static::assertSame('A', $function->getThrows()[0]->getType());
        static::assertSame('\N\A', $function->getThrows()[0]->getFqcn());
        static::assertNull($function->getThrows()[0]->getDescription());

        static::assertSame('\Exception', $function->getThrows()[1]->getType());
        static::assertSame('\Exception', $function->getThrows()[1]->getFqcn());
        static::assertSame('when something goes wrong.', $function->getThrows()[1]->getDescription());
    }

    /**
     * @return void
     */
    public function testFunctionSimpleParameters(): void
    {
        $function = $this->indexFunction('FunctionSimpleParameters.phpt');

        static::assertCount(2, $function->getParameters());

        $parameter = $function->getParameters()[0];

        static::assertSame($function, $parameter->getFunction());
        static::assertSame('a', $parameter->getName());
        static::assertNull($parameter->getTypeHint());
        static::assertEmpty($parameter->getTypes());
        static::assertNull($parameter->getDescription());
        static::assertNull($parameter->getDefaultValue());
        static::assertFalse($parameter->getIsReference());
        static::assertFalse($parameter->getIsOptional());
        static::assertFalse($parameter->getIsVariadic());

        $parameter = $function->getParameters()[1];

        static::assertSame($function, $parameter->getFunction());
        static::assertSame('b', $parameter->getName());
        static::assertNull($parameter->getTypeHint());
        static::assertEmpty($parameter->getTypes());
        static::assertNull($parameter->getDescription());
        static::assertNull($parameter->getDefaultValue());
        static::assertFalse($parameter->getIsReference());
        static::assertFalse($parameter->getIsOptional());
        static::assertFalse($parameter->getIsVariadic());
    }

    /**
     * @return void
     */
    public function testFunctionParameterTypeHint(): void
    {
        $function = $this->indexFunction('FunctionParameterTypeHint.phpt');

        static::assertCount(1, $function->getParameters()[0]->getTypes());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getType());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getFqcn());
        static::assertSame('int', $function->getParameters()[0]->getTypeHint());
    }

    /**
     * @return void
     */
    public function testFunctionParameterTypeHintIsResolved(): void
    {
        $function = $this->indexFunction('FunctionParameterTypeHintIsResolved.phpt');

        static::assertCount(1, $function->getParameters()[0]->getTypes());
        static::assertSame('A', $function->getParameters()[0]->getTypes()[0]->getType());
        static::assertSame('\N\A', $function->getParameters()[0]->getTypes()[0]->getFqcn());
        static::assertSame('\N\A', $function->getParameters()[0]->getTypeHint());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDocblockType(): void
    {
        $function = $this->indexFunction('FunctionParameterDocblockType.phpt');

        static::assertCount(1, $function->getParameters()[0]->getTypes());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getType());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDocblockTypeIsResolved(): void
    {
        $function = $this->indexFunction('FunctionParameterDocblockTypeIsResolved.phpt');

        static::assertCount(1, $function->getParameters()[0]->getTypes());
        static::assertSame('A', $function->getParameters()[0]->getTypes()[0]->getType());
        static::assertSame('\N\A', $function->getParameters()[0]->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDocblockTypeGetsPrecedenceOverTypeHint(): void
    {
        $function = $this->indexFunction('FunctionParameterDocblockTypePrecedenceOverTypeHint.phpt');

        static::assertCount(1, $function->getParameters()[0]->getTypes());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getType());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDefaultValue(): void
    {
        $function = $this->indexFunction('FunctionParameterDefaultValue.phpt');

        static::assertSame('5', $function->getParameters()[0]->getDefaultValue());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDefaultValueTypeDeduction(): void
    {
        $function = $this->indexFunction('FunctionParameterDefaultValueTypeDeduction.phpt');

        static::assertCount(1, $function->getParameters()[0]->getTypes());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getType());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionParameterTypeHintGetsPrecedenceOverDefaultValueTypeDeduction(): void
    {
        $function = $this->indexFunction('FunctionParameterTypeHintGetsPrecedenceOverDefaultValueTypeDeduction.phpt');

        static::assertCount(1, $function->getParameters()[0]->getTypes());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getType());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionParameterExplicitNullability(): void
    {
        $function = $this->indexFunction('FunctionParameterExplicitNullability.phpt');

        static::assertSame('?int', $function->getParameters()[0]->getTypeHint());

        static::assertCount(2, $function->getParameters()[0]->getTypes());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getType());
        static::assertSame('null', $function->getParameters()[0]->getTypes()[1]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionParameterImplicitNullability(): void
    {
        $function = $this->indexFunction('FunctionParameterImplicitNullability.phpt');

        static::assertSame('int', $function->getParameters()[0]->getTypeHint());

        static::assertCount(2, $function->getParameters()[0]->getTypes());
        static::assertSame('int', $function->getParameters()[0]->getTypes()[0]->getType());
        static::assertSame('null', $function->getParameters()[0]->getTypes()[1]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionReferenceParameter(): void
    {
        $function = $this->indexFunction('FunctionReferenceParameter.phpt');

        static::assertTrue($function->getParameters()[0]->getIsReference());
    }

    /**
     * @return void
     */
    public function testFunctionVariadicParameter(): void
    {
        $function = $this->indexFunction('FunctionVariadicParameter.phpt');

        static::assertTrue($function->getParameters()[0]->getIsVariadic());

        static::assertCount(1, $function->getParameters()[0]->getTypes());
        static::assertSame('int[]', $function->getParameters()[0]->getTypes()[0]->getType());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $functions = $this->container->get('managerRegistry')->getRepository(Structures\Function_::class)->findAll();

            static::assertCount(1, $functions);
            static::assertSame('\foo', $functions[0]->getFqcn());

            return str_replace('foo', 'foo2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $functions = $this->container->get('managerRegistry')->getRepository(Structures\Function_::class)->findAll();

            static::assertCount(1, $functions);
            static::assertSame('\foo2', $functions[0]->getFqcn());
        };

        $path = $this->getPathFor('FunctionChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Function_
     */
    private function indexFunction(string $file): Structures\Function_
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $functions = $this->container->get('managerRegistry')->getRepository(Structures\Function_::class)->findAll();

        static::assertCount(1, $functions);

        return $functions[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return __DIR__ . '/FunctionIndexingTest/' . $file;
    }
}
