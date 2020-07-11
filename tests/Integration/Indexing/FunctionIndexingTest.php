<?php

namespace Serenata\Tests\Integration\Indexing;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FunctionIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleFunction(): void
    {
        $function = $this->indexFunction('SimpleFunction.phpt');

        self::assertSame('foo', $function->getName());
        self::assertSame('\foo', $function->getFqcn());
        self::assertSame($this->normalizePath($this->getPathFor('SimpleFunction.phpt')), $function->getFile()->getUri());
        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(5, 1)
            ),
            $function->getRange()
        );
        self::assertFalse($function->getIsDeprecated());
        self::assertNull($function->getShortDescription());
        self::assertNull($function->getLongDescription());
        self::assertNull($function->getReturnDescription());
        self::assertNull($function->getReturnTypeHint());
        self::assertFalse($function->getHasDocblock());
        self::assertEmpty($function->getThrows());
        self::assertEmpty($function->getParameters());
        self::assertSame('mixed', (string) $function->getReturnType());
    }

    /**
     * @return void
     */
    public function testDeprecatedFunction(): void
    {
        $function = $this->indexFunction('DeprecatedFunction.phpt');

        self::assertTrue($function->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testFunctionShortDescription(): void
    {
        $function = $this->indexFunction('FunctionShortDescription.phpt');

        self::assertSame('This is a summary.', $function->getShortDescription());
    }

    /**
     * @return void
     */
    public function testFunctionLongDescription(): void
    {
        $function = $this->indexFunction('FunctionLongDescription.phpt');

        self::assertSame('This is a long description.', $function->getLongDescription());
    }

    /**
     * @return void
     */
    public function testFunctionReturnDescription(): void
    {
        $function = $this->indexFunction('FunctionReturnDescription.phpt');

        self::assertSame('This is a return description.', $function->getReturnDescription());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeIsFetchedFromDocblockAndGetsPrecedenceOverReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeFromDocblock.phpt');

        self::assertSame('int', (string) $function->getReturnType());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeIsFetchedFromReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeFromTypeHint.phpt');

        self::assertSame('string', (string) $function->getReturnType());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeHint.phpt');

        self::assertSame('string', (string) $function->getReturnType());
    }

    /**
     * @return void
     */
    public function testFunctionExplicitlyNullableReturnTypeHint(): void
    {
        $function = $this->indexFunction('FunctionExplicitlyNullableReturnTypeHint.phpt');

        self::assertSame('?string', $function->getReturnTypeHint());
        self::assertSame('(string | null)', (string) $function->getReturnType());
    }

    /**
     * @return void
     */
    public function testFunctionFqcnIsInCurrentNamespace(): void
    {
        $function = $this->indexFunction('FunctionFqcnInNamespace.phpt');

        self::assertSame('\A\foo', $function->getFqcn());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeInDocblockIsResolved(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeInDocblockIsResolved.phpt');

        self::assertSame('\N\A', (string) $function->getReturnType());
    }

    /**
     * @return void
     */
    public function testFunctionReturnTypeInReturnTypeHintIsResolved(): void
    {
        $function = $this->indexFunction('FunctionReturnTypeInReturnTypeHintIsResolved.phpt');

        self::assertSame('\N\A', (string) $function->getReturnType());
        self::assertSame('\N\A', $function->getReturnTypeHint());
    }

    /**
     * @return void
     */
    public function testFunctionThrows(): void
    {
        $function = $this->indexFunction('FunctionThrows.phpt');

        self::assertCount(2, $function->getThrows());

        self::assertSame('A', $function->getThrows()[0]->getType());
        self::assertSame('\N\A', $function->getThrows()[0]->getFqcn());
        self::assertNull($function->getThrows()[0]->getDescription());

        self::assertSame('\Exception', $function->getThrows()[1]->getType());
        self::assertSame('\Exception', $function->getThrows()[1]->getFqcn());
        self::assertSame('when something goes wrong.', $function->getThrows()[1]->getDescription());
    }

    /**
     * @return void
     */
    public function testFunctionSimpleParameters(): void
    {
        $function = $this->indexFunction('FunctionSimpleParameters.phpt');

        self::assertCount(2, $function->getParameters());

        $parameter = $function->getParameters()[0];

        self::assertSame($function, $parameter->getFunction());
        self::assertSame('a', $parameter->getName());
        self::assertNull($parameter->getTypeHint());
        self::assertSame('mixed', (string) $parameter->getType());
        self::assertNull($parameter->getDescription());
        self::assertNull($parameter->getDefaultValue());
        self::assertFalse($parameter->getIsReference());
        self::assertFalse($parameter->getIsOptional());
        self::assertFalse($parameter->getIsVariadic());

        $parameter = $function->getParameters()[1];

        self::assertSame($function, $parameter->getFunction());
        self::assertSame('b', $parameter->getName());
        self::assertNull($parameter->getTypeHint());
        self::assertSame('mixed', (string) $parameter->getType());
        self::assertNull($parameter->getDescription());
        self::assertNull($parameter->getDefaultValue());
        self::assertFalse($parameter->getIsReference());
        self::assertFalse($parameter->getIsOptional());
        self::assertFalse($parameter->getIsVariadic());
    }

    /**
     * @return void
     */
    public function testFunctionParameterTypeHint(): void
    {
        $function = $this->indexFunction('FunctionParameterTypeHint.phpt');

        self::assertSame('int', (string) $function->getParameters()[0]->getType());
        self::assertSame('int', $function->getParameters()[0]->getTypeHint());
    }

    /**
     * @return void
     */
    public function testFunctionParameterTypeHintIsResolved(): void
    {
        $function = $this->indexFunction('FunctionParameterTypeHintIsResolved.phpt');

        self::assertSame('\N\A', (string) $function->getParameters()[0]->getType());
        self::assertSame('\N\A', $function->getParameters()[0]->getTypeHint());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDocblockType(): void
    {
        $function = $this->indexFunction('FunctionParameterDocblockType.phpt');

        self::assertSame('int', (string) $function->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDocblockTypeIsResolved(): void
    {
        $function = $this->indexFunction('FunctionParameterDocblockTypeIsResolved.phpt');

        self::assertSame('\N\A', (string) $function->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDocblockTypeGetsPrecedenceOverTypeHint(): void
    {
        $function = $this->indexFunction('FunctionParameterDocblockTypePrecedenceOverTypeHint.phpt');

        self::assertSame('int', (string) $function->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDefaultValue(): void
    {
        $function = $this->indexFunction('FunctionParameterDefaultValue.phpt');

        self::assertSame('5', $function->getParameters()[0]->getDefaultValue());
    }

    /**
     * @return void
     */
    public function testFunctionParameterDefaultValueTypeDeduction(): void
    {
        $function = $this->indexFunction('FunctionParameterDefaultValueTypeDeduction.phpt');

        self::assertSame('int', (string) $function->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionParameterTypeHintGetsPrecedenceOverDefaultValueTypeDeduction(): void
    {
        $function = $this->indexFunction('FunctionParameterTypeHintGetsPrecedenceOverDefaultValueTypeDeduction.phpt');

        self::assertSame('int', (string) $function->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionParameterExplicitNullability(): void
    {
        $function = $this->indexFunction('FunctionParameterExplicitNullability.phpt');

        self::assertSame('?int', $function->getParameters()[0]->getTypeHint());
        self::assertSame('(int | null)', (string) $function->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionParameterImplicitNullability(): void
    {
        $function = $this->indexFunction('FunctionParameterImplicitNullability.phpt');

        self::assertSame('int', $function->getParameters()[0]->getTypeHint());
        self::assertSame('(int | null)', (string) $function->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionReferenceParameter(): void
    {
        $function = $this->indexFunction('FunctionReferenceParameter.phpt');

        self::assertTrue($function->getParameters()[0]->getIsReference());
    }

    /**
     * @return void
     */
    public function testFunctionVariadicParameter(): void
    {
        $function = $this->indexFunction('FunctionVariadicParameter.phpt');

        self::assertTrue($function->getParameters()[0]->getIsVariadic());

        self::assertSame('int[]', (string) $function->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testFunctionOnLastLineDoesNotGenerateAnyProblems(): void
    {
        $function = $this->indexFunction('FunctionOnLastLine.phpt');

        self::assertSame('foo', $function->getName());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $functions = $this->container->get('managerRegistry')->getRepository(Structures\Function_::class)->findAll();

            self::assertCount(1, $functions);
            self::assertSame('\foo', $functions[0]->getFqcn());

            return str_replace('foo', 'foo2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $functions = $this->container->get('managerRegistry')->getRepository(Structures\Function_::class)->findAll();

            self::assertCount(1, $functions);
            self::assertSame('\foo2', $functions[0]->getFqcn());
        };

        $path = $this->getPathFor('FunctionChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
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

        self::assertCount(1, $functions);

        return $functions[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/FunctionIndexingTest/' . $file;
    }
}
