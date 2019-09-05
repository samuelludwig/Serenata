<?php

namespace Serenata\Tests\Integration\Tooltips;

use Serenata\Indexing\Structures\MetaStaticMethodType;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use PhpParser\Node;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MetaStaticMethodTypesIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testStaticMethodTypes(): void
    {
        $path = $this->getPathFor('StaticMethodTypes.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\A\Foo', 'get');

        static::assertCount(2, $types);

        static::assertSame(0, $types[0]->getArgumentIndex());
        static::assertSame('bar', $types[0]->getValue());
        static::assertSame(Node\Scalar\String_::class, $types[0]->getValueNodeType());
        static::assertSame('\B\Bar', $types[0]->getReturnType());

        static::assertSame(0, $types[1]->getArgumentIndex());
        static::assertSame('car', $types[1]->getValue());
        static::assertSame(Node\Scalar\String_::class, $types[1]->getValueNodeType());
        static::assertSame('\B\Car', $types[1]->getReturnType());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\A\Foo', 'get');

            static::assertCount(1, $types);

            static::assertSame(0, $types[0]->getArgumentIndex());
            static::assertSame('bar', $types[0]->getValue());
            static::assertSame(Node\Scalar\String_::class, $types[0]->getValueNodeType());
            static::assertSame('\A\Bar', $types[0]->getReturnType());

            return str_replace('\A\\', '\B\\', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\A\Foo', 'get');
            static::assertCount(0, $types);

            $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\B\Foo', 'get');
            static::assertCount(1, $types);

            static::assertSame(0, $types[0]->getArgumentIndex());
            static::assertSame('bar', $types[0]->getValue());
            static::assertSame(Node\Scalar\String_::class, $types[0]->getValueNodeType());
            static::assertSame('\B\Bar', $types[0]->getReturnType());
        };

        $path = $this->getPathFor('StaticMethodTypeChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testSkipsIfStaticMethodTypesIsNotArray(): void
    {
        $path = $this->getPathFor('StaticMethodTypesIsNotArray.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        static::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemIsNotArray(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemIsNotArray.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        static::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemKeyIsNotString(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemKeyIsNotString.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        static::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemKeywordIsNotInstanceof(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemKeywordIsNotInstanceof.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        static::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemValueIsNotName(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemValueIsNotName.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        static::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesKeyClassIsNotName(): void
    {
        $path = $this->getPathFor('StaticMethodTypesKeyClassIsNotName.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        static::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesKeyIsNotStaticMethodCall(): void
    {
        $path = $this->getPathFor('StaticMethodTypesKeyIsNotStaticMethodCall.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        static::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesKeyMethodNameIsNotIdentifier(): void
    {
        $path = $this->getPathFor('StaticMethodTypesKeyMethodNameIsNotIdentifier.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        static::assertCount(0, $types);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/MetaStaticMethodTypesIndexingTest/' . $file;
    }
}
