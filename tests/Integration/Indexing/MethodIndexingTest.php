<?php

namespace Serenata\Tests\Integration\Tooltips;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Indexing\Structures\AccessModifierNameValue;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MethodIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleMethod(): void
    {
        $method = $this->indexMethod('SimpleMethod.phpt');

        static::assertSame('foo', $method->getName());
        static::assertSame($this->normalizePath($this->getPathFor('SimpleMethod.phpt')), $method->getFile()->getUri());
        static::assertEquals(
            new Range(
                new Position(4, 4),
                new Position(7, 5)
            ),
            $method->getRange()
        );
        static::assertFalse($method->getIsDeprecated());
        static::assertNull($method->getShortDescription());
        static::assertNull($method->getLongDescription());
        static::assertNull($method->getReturnDescription());
        static::assertNull($method->getReturnTypeHint());
        static::assertFalse($method->getHasDocblock());
        static::assertEmpty($method->getThrows());
        static::assertEmpty($method->getParameters());
        static::assertSame('mixed', $method->getReturnType()->toString());
        static::assertFalse($method->getIsMagic());
        static::assertFalse($method->getIsStatic());
        static::assertFalse($method->getIsAbstract());
        static::assertFalse($method->getIsFinal());
        static::assertSame(AccessModifierNameValue::PUBLIC_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testDeprecatedMethod(): void
    {
        $method = $this->indexMethod('DeprecatedMethod.phpt');

        static::assertTrue($method->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testMethodShortDescription(): void
    {
        $method = $this->indexMethod('MethodShortDescription.phpt');

        static::assertSame('This is a summary.', $method->getShortDescription());
    }

    /**
     * @return void
     */
    public function testMethodLongDescription(): void
    {
        $method = $this->indexMethod('MethodLongDescription.phpt');

        static::assertSame('This is a long description.', $method->getLongDescription());
    }

    /**
     * @return void
     */
    public function testMethodReturnDescription(): void
    {
        $method = $this->indexMethod('MethodReturnDescription.phpt');

        static::assertSame('This is a return description.', $method->getReturnDescription());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeIsFetchedFromDocblockAndGetsPrecedenceOverReturnTypeHint(): void
    {
        $method = $this->indexMethod('MethodReturnTypeFromDocblock.phpt');

        static::assertSame('int', $method->getReturnType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeIsFetchedFromReturnTypeHint(): void
    {
        $method = $this->indexMethod('MethodReturnTypeFromTypeHint.phpt');

        static::assertSame('string', $method->getReturnType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeHint(): void
    {
        $method = $this->indexMethod('MethodReturnTypeHint.phpt');

        static::assertSame('string', $method->getReturnTypeHint());
    }

    /**
     * @return void
     */
    public function testMethodExplicitlyNullableReturnTypeHint(): void
    {
        $method = $this->indexMethod('MethodExplicitlyNullableReturnTypeHint.phpt');

        static::assertSame('?string', $method->getReturnTypeHint());
        static::assertSame('string|null', $method->getReturnType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeInDocblockIsResolved(): void
    {
        $method = $this->indexMethod('MethodReturnTypeInDocblockIsResolved.phpt');

        static::assertSame('\N\A', $method->getReturnType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeInReturnTypeHintIsResolved(): void
    {
        $method = $this->indexMethod('MethodReturnTypeInReturnTypeHintIsResolved.phpt');

        static::assertSame('\N\A', $method->getReturnType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodThrows(): void
    {
        $method = $this->indexMethod('MethodThrows.phpt');

        static::assertCount(2, $method->getThrows());

        static::assertSame('A', $method->getThrows()[0]->getType());
        static::assertSame('\N\A', $method->getThrows()[0]->getFqcn());
        static::assertNull($method->getThrows()[0]->getDescription());

        static::assertSame('\Exception', $method->getThrows()[1]->getType());
        static::assertSame('\Exception', $method->getThrows()[1]->getFqcn());
        static::assertSame('when something goes wrong.', $method->getThrows()[1]->getDescription());
    }

    /**
     * @return void
     */
    public function testMethodSimpleParameters(): void
    {
        $method = $this->indexMethod('MethodSimpleParameters.phpt');

        static::assertCount(2, $method->getParameters());

        $parameter = $method->getParameters()[0];

        static::assertSame($method, $parameter->getMethod());
        static::assertSame('a', $parameter->getName());
        static::assertNull($parameter->getTypeHint());
        static::assertSame('mixed', $parameter->getType()->toString());
        static::assertNull($parameter->getDescription());
        static::assertNull($parameter->getDefaultValue());
        static::assertFalse($parameter->getIsReference());
        static::assertFalse($parameter->getIsOptional());
        static::assertFalse($parameter->getIsVariadic());

        $parameter = $method->getParameters()[1];

        static::assertSame($method, $parameter->getMethod());
        static::assertSame('b', $parameter->getName());
        static::assertNull($parameter->getTypeHint());
        static::assertSame('mixed', $parameter->getType()->toString());
        static::assertNull($parameter->getDescription());
        static::assertNull($parameter->getDefaultValue());
        static::assertFalse($parameter->getIsReference());
        static::assertFalse($parameter->getIsOptional());
        static::assertFalse($parameter->getIsVariadic());
    }

    /**
     * @return void
     */
    public function testMethodParameterTypeHint(): void
    {
        $method = $this->indexMethod('MethodParameterTypeHint.phpt');

        static::assertSame('int', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodParameterTypeHintIsResolved(): void
    {
        $method = $this->indexMethod('MethodParameterTypeHintIsResolved.phpt');

        static::assertSame('\N\A', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodParameterDocblockType(): void
    {
        $method = $this->indexMethod('MethodParameterDocblockType.phpt');

        static::assertSame('int', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodParameterDocblockTypeIsResolved(): void
    {
        $method = $this->indexMethod('MethodParameterDocblockTypeIsResolved.phpt');

        static::assertSame('\N\A', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodParameterDocblockTypeGetsPrecedenceOverTypeHint(): void
    {
        $method = $this->indexMethod('MethodParameterDocblockTypePrecedenceOverTypeHint.phpt');

        static::assertSame('int', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodParameterDefaultValue(): void
    {
        $method = $this->indexMethod('MethodParameterDefaultValue.phpt');

        static::assertSame('5', $method->getParameters()[0]->getDefaultValue());
    }

    /**
     * @return void
     */
    public function testMethodParameterDefaultValueTypeDeduction(): void
    {
        $method = $this->indexMethod('MethodParameterDefaultValueTypeDeduction.phpt');

        static::assertSame('int', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodParameterTypeHintGetsPrecedenceOverDefaultValueTypeDeduction(): void
    {
        $method = $this->indexMethod('MethodParameterTypeHintGetsPrecedenceOverDefaultValueTypeDeduction.phpt');

        static::assertSame('int', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodParameterExplicitNullability(): void
    {
        $method = $this->indexMethod('MethodParameterExplicitNullability.phpt');

        static::assertSame('?int', $method->getParameters()[0]->getTypeHint());
        static::assertSame('int|null', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodParameterImplicitNullability(): void
    {
        $method = $this->indexMethod('MethodParameterImplicitNullability.phpt');

        static::assertSame('int', $method->getParameters()[0]->getTypeHint());
        static::assertSame('int|null', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testMethodReferenceParameter(): void
    {
        $method = $this->indexMethod('MethodReferenceParameter.phpt');

        static::assertTrue($method->getParameters()[0]->getIsReference());
    }

    /**
     * @return void
     */
    public function testMethodVariadicParameter(): void
    {
        $method = $this->indexMethod('MethodVariadicParameter.phpt');

        static::assertTrue($method->getParameters()[0]->getIsVariadic());
        static::assertSame('int[]', $method->getParameters()[0]->getType()->toString());
    }

    /**
     * @return void
     */
    public function testFinalMethod(): void
    {
        $method = $this->indexMethod('FinalMethod.phpt');

        static::assertTrue($method->getIsFinal());
    }

    /**
     * @return void
     */
    public function testAbstractMethod(): void
    {
        $method = $this->indexMethod('AbstractMethod.phpt');

        static::assertTrue($method->getIsAbstract());
    }

    /**
     * @return void
     */
    public function testMagicMethod(): void
    {
        $method = $this->indexMethod('MagicMethod.phpt');

        static::assertTrue($method->getIsMagic());
        static::assertFalse($method->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicMethodWithReturnType(): void
    {
        $method = $this->indexMethod('MagicMethodWithReturnType.phpt');

        static::assertSame('int', $method->getReturnType()->toString());
    }

    /**
     * @return void
     */
    public function testMagicMethodReturnTypeIsResolved(): void
    {
        $method = $this->indexMethod('MagicMethodReturnTypeIsResolved.phpt');

        static::assertSame('\N\A', $method->getReturnType()->toString());
    }

    /**
     * @return void
     */
    public function testMagicMethodWithDescription(): void
    {
        $method = $this->indexMethod('MagicMethodWithDescription.phpt');

        static::assertSame('A summary.', $method->getShortDescription());
    }

    /**
     * @return void
     */
    public function testMagicMethodOmittingReturnType(): void
    {
        $method = $this->indexMethod('MagicMethodOmittingReturnType.phpt');

        static::assertSame('void', $method->getReturnType()->toString());
    }

    /**
     * @return void
     */
    public function testMagicMethodWithDescriptionWithoutReturnType(): void
    {
        $method = $this->indexMethod('MagicMethodWithDescriptionWithoutReturnType.phpt');

        static::assertSame('A summary.', $method->getShortDescription());

        static::assertSame('void', $method->getReturnType()->toString());
    }

    /**
     * @return void
     */
    public function testStaticMagicMethodWithReturnType(): void
    {
        $method = $this->indexMethod('StaticMagicMethodWithReturnType.phpt');

        static::assertTrue($method->getIsStatic());
    }

    /**
     * @return void
     */
    public function testStaticMagicMethodWithoutReturnType(): void
    {
        $method = $this->indexMethod('StaticMagicMethodWithoutReturnType.phpt');

        static::assertTrue($method->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicMethodWithRequiredParameter(): void
    {
        $method = $this->indexMethod('MagicMethodWithRequiredParameter.phpt');

        static::assertCount(1, $method->getParameters());

        $parameter = $method->getParameters()[0];

        static::assertSame($method, $parameter->getMethod());
        static::assertSame('a', $parameter->getName());
        static::assertNull($parameter->getTypeHint());
        static::assertSame('mixed', $parameter->getType()->toString());
        static::assertNull($parameter->getDescription());
        static::assertNull($parameter->getDefaultValue());
        static::assertFalse($parameter->getIsReference());
        static::assertFalse($parameter->getIsOptional());
        static::assertFalse($parameter->getIsVariadic());
    }

    /**
     * @return void
     */
    public function testMagicMethodWithOptionalParameter(): void
    {
        $method = $this->indexMethod('MagicMethodWithOptionalParameter.phpt');

        static::assertCount(1, $method->getParameters());

        $parameter = $method->getParameters()[0];

        static::assertSame($method, $parameter->getMethod());
        static::assertSame('a', $parameter->getName());
        static::assertNull($parameter->getTypeHint());
        static::assertSame('mixed', $parameter->getType()->toString());
        static::assertNull($parameter->getDescription());
        static::assertNull($parameter->getDefaultValue());
        static::assertFalse($parameter->getIsReference());
        static::assertTrue($parameter->getIsOptional());
        static::assertFalse($parameter->getIsVariadic());
    }

    /**
     * @return void
     */
    public function testMagicMethodParameterTypeIsResolved(): void
    {
        $method = $this->indexMethod('MagicMethodParameterTypeIsResolved.phpt');

        static::assertCount(1, $method->getParameters());

        $parameter = $method->getParameters()[0];

        static::assertSame('\N\A', $parameter->getType()->toString());
    }

    /**
     * @return void
     */
    public function testStaticMethod(): void
    {
        $method = $this->indexMethod('StaticMethod.phpt');

        static::assertTrue($method->getIsStatic());
    }

    /**
     * @return void
     */
    public function testImplicitlyPublicMethod(): void
    {
        $method = $this->indexMethod('ImplicitlyPublicMethod.phpt');

        static::assertSame(AccessModifierNameValue::PUBLIC_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testExplicitlyPublicMethod(): void
    {
        $method = $this->indexMethod('ExplicitlyPublicMethod.phpt');

        static::assertSame(AccessModifierNameValue::PUBLIC_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testProtectedMethod(): void
    {
        $method = $this->indexMethod('ProtectedMethod.phpt');

        static::assertSame(AccessModifierNameValue::PROTECTED_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testPrivateMethod(): void
    {
        $method = $this->indexMethod('PrivateMethod.phpt');

        static::assertSame(AccessModifierNameValue::PRIVATE_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(1, $classes);
            static::assertCount(1, $classes[0]->getMethods());

            $method = $classes[0]->getMethods()[0];

            static::assertSame('foo', $method->getName());

            return str_replace('foo', 'foo2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(1, $classes);
            static::assertCount(1, $classes[0]->getMethods());

            $method = $classes[0]->getMethods()[0];

            static::assertSame('foo2', $method->getName());
        };

        $path = $this->getPathFor('MethodChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Method
     */
    private function indexMethod(string $file): Structures\Method
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        static::assertCount(1, $classes);
        static::assertCount(1, $classes[0]->getMethods());

        $method = $classes[0]->getMethods()[0];

        static::assertSame($classes[0], $method->getClasslike());

        return $method;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/MethodIndexingTest/' . $file;
    }
}
