<?php

namespace Serenata\Tests\Integration\Indexing;

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

        self::assertSame('foo', $method->getName());
        self::assertSame($this->normalizePath($this->getPathFor('SimpleMethod.phpt')), $method->getFile()->getUri());
        self::assertEquals(
            new Range(
                new Position(4, 4),
                new Position(7, 5)
            ),
            $method->getRange()
        );
        self::assertFalse($method->getIsDeprecated());
        self::assertNull($method->getShortDescription());
        self::assertNull($method->getLongDescription());
        self::assertNull($method->getReturnDescription());
        self::assertNull($method->getReturnTypeHint());
        self::assertFalse($method->getHasDocblock());
        self::assertEmpty($method->getThrows());
        self::assertEmpty($method->getParameters());
        self::assertSame('mixed', (string) $method->getReturnType());
        self::assertFalse($method->getIsMagic());
        self::assertFalse($method->getIsStatic());
        self::assertFalse($method->getIsAbstract());
        self::assertFalse($method->getIsFinal());
        self::assertSame(AccessModifierNameValue::PUBLIC_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testDeprecatedMethod(): void
    {
        $method = $this->indexMethod('DeprecatedMethod.phpt');

        self::assertTrue($method->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testMethodShortDescription(): void
    {
        $method = $this->indexMethod('MethodShortDescription.phpt');

        self::assertSame('This is a summary.', $method->getShortDescription());
    }

    /**
     * @return void
     */
    public function testMethodLongDescription(): void
    {
        $method = $this->indexMethod('MethodLongDescription.phpt');

        self::assertSame('This is a long description.', $method->getLongDescription());
    }

    /**
     * @return void
     */
    public function testMethodReturnDescription(): void
    {
        $method = $this->indexMethod('MethodReturnDescription.phpt');

        self::assertSame('This is a return description.', $method->getReturnDescription());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeIsFetchedFromDocblockAndGetsPrecedenceOverReturnTypeHint(): void
    {
        $method = $this->indexMethod('MethodReturnTypeFromDocblock.phpt');

        self::assertSame('int', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeIsFetchedFromReturnTypeHint(): void
    {
        $method = $this->indexMethod('MethodReturnTypeFromTypeHint.phpt');

        self::assertSame('string', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeHint(): void
    {
        $method = $this->indexMethod('MethodReturnTypeHint.phpt');

        self::assertSame('string', $method->getReturnTypeHint());
    }

    /**
     * @return void
     */
    public function testMethodExplicitlyNullableReturnTypeHint(): void
    {
        $method = $this->indexMethod('MethodExplicitlyNullableReturnTypeHint.phpt');

        self::assertSame('?string', $method->getReturnTypeHint());
        self::assertSame('(string | null)', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeInDocblockIsResolved(): void
    {
        $method = $this->indexMethod('MethodReturnTypeInDocblockIsResolved.phpt');

        self::assertSame('\N\A', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testMethodUnionReturnTypeHint(): void
    {
        $method = $this->indexMethod('MethodUnionReturnTypeHint.phpt');

        self::assertSame('(\N\A | \N\B)', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testMethodReturnTypeInReturnTypeHintIsResolved(): void
    {
        $method = $this->indexMethod('MethodReturnTypeInReturnTypeHintIsResolved.phpt');

        self::assertSame('\N\A', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testMethodThrows(): void
    {
        $method = $this->indexMethod('MethodThrows.phpt');

        self::assertCount(2, $method->getThrows());

        self::assertSame('A', $method->getThrows()[0]->getType());
        self::assertSame('\N\A', $method->getThrows()[0]->getFqcn());
        self::assertNull($method->getThrows()[0]->getDescription());

        self::assertSame('\Exception', $method->getThrows()[1]->getType());
        self::assertSame('\Exception', $method->getThrows()[1]->getFqcn());
        self::assertSame('when something goes wrong.', $method->getThrows()[1]->getDescription());
    }

    /**
     * @return void
     */
    public function testMethodSimpleParameters(): void
    {
        $method = $this->indexMethod('MethodSimpleParameters.phpt');

        self::assertCount(2, $method->getParameters());

        $parameter = $method->getParameters()[0];

        self::assertSame($method, $parameter->getMethod());
        self::assertSame('a', $parameter->getName());
        self::assertNull($parameter->getTypeHint());
        self::assertSame('mixed', (string) $parameter->getType());
        self::assertNull($parameter->getDescription());
        self::assertNull($parameter->getDefaultValue());
        self::assertFalse($parameter->getIsReference());
        self::assertFalse($parameter->getIsOptional());
        self::assertFalse($parameter->getIsVariadic());

        $parameter = $method->getParameters()[1];

        self::assertSame($method, $parameter->getMethod());
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
    public function testMethodParameterTypeHint(): void
    {
        $method = $this->indexMethod('MethodParameterTypeHint.phpt');

        self::assertSame('int', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodParameterTypeHintIsResolved(): void
    {
        $method = $this->indexMethod('MethodParameterTypeHintIsResolved.phpt');

        self::assertSame('\N\A', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodParameterUnionTypeHint(): void
    {
        $method = $this->indexMethod('MethodParameterUnionTypeHint.phpt');

        self::assertSame('(\N\A | \N\B)', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodParameterDocblockType(): void
    {
        $method = $this->indexMethod('MethodParameterDocblockType.phpt');

        self::assertSame('int', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodParameterDocblockTypeIsResolved(): void
    {
        $method = $this->indexMethod('MethodParameterDocblockTypeIsResolved.phpt');

        self::assertSame('\N\A', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodParameterDocblockTypeGetsPrecedenceOverTypeHint(): void
    {
        $method = $this->indexMethod('MethodParameterDocblockTypePrecedenceOverTypeHint.phpt');

        self::assertSame('int', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodParameterDefaultValue(): void
    {
        $method = $this->indexMethod('MethodParameterDefaultValue.phpt');

        self::assertSame('5', $method->getParameters()[0]->getDefaultValue());
    }

    /**
     * @return void
     */
    public function testMethodParameterDefaultValueTypeDeduction(): void
    {
        $method = $this->indexMethod('MethodParameterDefaultValueTypeDeduction.phpt');

        self::assertSame('int', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodParameterTypeHintGetsPrecedenceOverDefaultValueTypeDeduction(): void
    {
        $method = $this->indexMethod('MethodParameterTypeHintGetsPrecedenceOverDefaultValueTypeDeduction.phpt');

        self::assertSame('int', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodParameterExplicitNullability(): void
    {
        $method = $this->indexMethod('MethodParameterExplicitNullability.phpt');

        self::assertSame('?int', $method->getParameters()[0]->getTypeHint());
        self::assertSame('(int | null)', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodParameterImplicitNullability(): void
    {
        $method = $this->indexMethod('MethodParameterImplicitNullability.phpt');

        self::assertSame('int', $method->getParameters()[0]->getTypeHint());
        self::assertSame('(int | null)', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testMethodReferenceParameter(): void
    {
        $method = $this->indexMethod('MethodReferenceParameter.phpt');

        self::assertTrue($method->getParameters()[0]->getIsReference());
    }

    /**
     * @return void
     */
    public function testMethodVariadicParameter(): void
    {
        $method = $this->indexMethod('MethodVariadicParameter.phpt');

        self::assertTrue($method->getParameters()[0]->getIsVariadic());
        self::assertSame('int[]', (string) $method->getParameters()[0]->getType());
    }

    /**
     * @return void
     */
    public function testFinalMethod(): void
    {
        $method = $this->indexMethod('FinalMethod.phpt');

        self::assertTrue($method->getIsFinal());
    }

    /**
     * @return void
     */
    public function testAbstractMethod(): void
    {
        $method = $this->indexMethod('AbstractMethod.phpt');

        self::assertTrue($method->getIsAbstract());
    }

    /**
     * @return void
     */
    public function testMagicMethod(): void
    {
        $method = $this->indexMethod('MagicMethod.phpt');

        self::assertTrue($method->getIsMagic());
        self::assertFalse($method->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicMethodWithReturnType(): void
    {
        $method = $this->indexMethod('MagicMethodWithReturnType.phpt');

        self::assertSame('int', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testMagicMethodReturnTypeIsResolved(): void
    {
        $method = $this->indexMethod('MagicMethodReturnTypeIsResolved.phpt');

        self::assertSame('\N\A', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testMagicMethodWithDescription(): void
    {
        $method = $this->indexMethod('MagicMethodWithDescription.phpt');

        self::assertSame('A summary.', $method->getShortDescription());
    }

    /**
     * @return void
     */
    public function testMagicMethodOmittingReturnType(): void
    {
        $method = $this->indexMethod('MagicMethodOmittingReturnType.phpt');

        self::assertSame('void', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testMagicMethodWithDescriptionWithoutReturnType(): void
    {
        $method = $this->indexMethod('MagicMethodWithDescriptionWithoutReturnType.phpt');

        self::assertSame('A summary.', $method->getShortDescription());

        self::assertSame('void', (string) $method->getReturnType());
    }

    /**
     * @return void
     */
    public function testStaticMagicMethodWithReturnType(): void
    {
        $method = $this->indexMethod('StaticMagicMethodWithReturnType.phpt');

        self::assertTrue($method->getIsStatic());
    }

    /**
     * @return void
     */
    public function testStaticMagicMethodWithoutReturnType(): void
    {
        $method = $this->indexMethod('StaticMagicMethodWithoutReturnType.phpt');

        self::assertTrue($method->getIsStatic());
    }

    /**
     * @return void
     */
    public function testMagicMethodWithRequiredParameter(): void
    {
        $method = $this->indexMethod('MagicMethodWithRequiredParameter.phpt');

        self::assertCount(1, $method->getParameters());

        $parameter = $method->getParameters()[0];

        self::assertSame($method, $parameter->getMethod());
        self::assertSame('a', $parameter->getName());
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
    public function testMagicMethodWithOptionalParameter(): void
    {
        $method = $this->indexMethod('MagicMethodWithOptionalParameter.phpt');

        self::assertCount(1, $method->getParameters());

        $parameter = $method->getParameters()[0];

        self::assertSame($method, $parameter->getMethod());
        self::assertSame('a', $parameter->getName());
        self::assertNull($parameter->getTypeHint());
        self::assertSame('mixed', (string) $parameter->getType());
        self::assertNull($parameter->getDescription());
        self::assertNull($parameter->getDefaultValue());
        self::assertFalse($parameter->getIsReference());
        self::assertTrue($parameter->getIsOptional());
        self::assertFalse($parameter->getIsVariadic());
    }

    /**
     * @return void
     */
    public function testMagicMethodParameterTypeIsResolved(): void
    {
        $method = $this->indexMethod('MagicMethodParameterTypeIsResolved.phpt');

        self::assertCount(1, $method->getParameters());

        $parameter = $method->getParameters()[0];

        self::assertSame('\N\A', (string) $parameter->getType());
    }

    /**
     * @return void
     */
    public function testStaticMethod(): void
    {
        $method = $this->indexMethod('StaticMethod.phpt');

        self::assertTrue($method->getIsStatic());
    }

    /**
     * @return void
     */
    public function testImplicitlyPublicMethod(): void
    {
        $method = $this->indexMethod('ImplicitlyPublicMethod.phpt');

        self::assertSame(AccessModifierNameValue::PUBLIC_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testExplicitlyPublicMethod(): void
    {
        $method = $this->indexMethod('ExplicitlyPublicMethod.phpt');

        self::assertSame(AccessModifierNameValue::PUBLIC_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testProtectedMethod(): void
    {
        $method = $this->indexMethod('ProtectedMethod.phpt');

        self::assertSame(AccessModifierNameValue::PROTECTED_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testPrivateMethod(): void
    {
        $method = $this->indexMethod('PrivateMethod.phpt');

        self::assertSame(AccessModifierNameValue::PRIVATE_, $method->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(1, $classes);
            self::assertCount(1, $classes[0]->getMethods());

            $method = $classes[0]->getMethods()[0];

            self::assertSame('foo', $method->getName());

            return str_replace('foo', 'foo2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $classes = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(1, $classes);
            self::assertCount(1, $classes[0]->getMethods());

            $method = $classes[0]->getMethods()[0];

            self::assertSame('foo2', $method->getName());
        };

        $path = $this->getPathFor('MethodChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
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

        self::assertCount(1, $classes);
        self::assertCount(1, $classes[0]->getMethods());

        $method = $classes[0]->getMethods()[0];

        self::assertSame($classes[0], $method->getClasslike());

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
