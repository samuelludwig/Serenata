<?php

namespace Serenata\Tests\Integration\Indexing;

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

        self::assertCount(2, $types);

        self::assertSame(0, $types[0]->getArgumentIndex());
        self::assertSame('bar', $types[0]->getValue());
        self::assertSame(Node\Scalar\String_::class, $types[0]->getValueNodeType());
        self::assertSame('\B\Bar', $types[0]->getReturnType());

        self::assertSame(0, $types[1]->getArgumentIndex());
        self::assertSame('car', $types[1]->getValue());
        self::assertSame(Node\Scalar\String_::class, $types[1]->getValueNodeType());
        self::assertSame('\B\Car', $types[1]->getReturnType());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\A\Foo', 'get');

            self::assertCount(1, $types);

            self::assertSame(0, $types[0]->getArgumentIndex());
            self::assertSame('bar', $types[0]->getValue());
            self::assertSame(Node\Scalar\String_::class, $types[0]->getValueNodeType());
            self::assertSame('\A\Bar', $types[0]->getReturnType());

            return str_replace('\A\\', '\B\\', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\A\Foo', 'get');
            self::assertCount(0, $types);

            $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\B\Foo', 'get');
            self::assertCount(1, $types);

            self::assertSame(0, $types[0]->getArgumentIndex());
            self::assertSame('bar', $types[0]->getValue());
            self::assertSame(Node\Scalar\String_::class, $types[0]->getValueNodeType());
            self::assertSame('\B\Bar', $types[0]->getReturnType());
        };

        $path = $this->getPathFor('StaticMethodTypeChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testSkipsIfStaticMethodTypesIsNotArray(): void
    {
        $path = $this->getPathFor('StaticMethodTypesIsNotArray.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        self::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemIsNotArray(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemIsNotArray.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        self::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemKeyIsNotString(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemKeyIsNotString.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        self::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemKeywordIsNotInstanceof(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemKeywordIsNotInstanceof.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        self::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemValueIsNotName(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemValueIsNotName.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        self::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesKeyClassIsNotName(): void
    {
        $path = $this->getPathFor('StaticMethodTypesKeyClassIsNotName.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        self::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesKeyIsNotStaticMethodCall(): void
    {
        $path = $this->getPathFor('StaticMethodTypesKeyIsNotStaticMethodCall.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        self::assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesKeyMethodNameIsNotIdentifier(): void
    {
        $path = $this->getPathFor('StaticMethodTypesKeyMethodNameIsNotIdentifier.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        self::assertCount(0, $types);
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
