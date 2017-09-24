<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\Structures\MetaStaticMethodType;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use PhpParser\Node;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class MetaStaticMethodTypesIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testStaticMethodTypes(): void
    {
        $path = $this->getPathFor('StaticMethodTypes.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\A\Foo', 'get');

        $this->assertCount(2, $types);

        $this->assertSame(0, $types[0]->getArgumentIndex());
        $this->assertSame('bar', $types[0]->getValue());
        $this->assertSame(Node\Scalar\String_::class, $types[0]->getValueNodeType());
        $this->assertSame('\B\Bar', $types[0]->getReturnType());

        $this->assertSame(0, $types[1]->getArgumentIndex());
        $this->assertSame('car', $types[1]->getValue());
        $this->assertSame(Node\Scalar\String_::class, $types[1]->getValueNodeType());
        $this->assertSame('\B\Car', $types[1]->getReturnType());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\A\Foo', 'get');

            $this->assertCount(1, $types);

            $this->assertSame(0, $types[0]->getArgumentIndex());
            $this->assertSame('bar', $types[0]->getValue());
            $this->assertSame(Node\Scalar\String_::class, $types[0]->getValueNodeType());
            $this->assertSame('\A\Bar', $types[0]->getReturnType());

            return str_replace('\A\\', '\B\\', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\A\Foo', 'get');
            $this->assertCount(0, $types);

            $types = $this->container->get('metadataProvider')->getMetaStaticMethodTypesFor('\B\Foo', 'get');
            $this->assertCount(1, $types);

            $this->assertSame(0, $types[0]->getArgumentIndex());
            $this->assertSame('bar', $types[0]->getValue());
            $this->assertSame(Node\Scalar\String_::class, $types[0]->getValueNodeType());
            $this->assertSame('\B\Bar', $types[0]->getReturnType());
        };

        $path = $this->getPathFor('StaticMethodTypeChanges.phpt');

        $this->assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testSkipsIfStaticMethodTypesIsNotArray(): void
    {
        $path = $this->getPathFor('StaticMethodTypesIsNotArray.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        $this->assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemIsNotArray(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemIsNotArray.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        $this->assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemKeyIsNotString(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemKeyIsNotString.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        $this->assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemKeywordIsNotInstanceof(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemKeywordIsNotInstanceof.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        $this->assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesItemValueIsNotName(): void
    {
        $path = $this->getPathFor('StaticMethodTypesItemValueIsNotName.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        $this->assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesKeyClassIsNotName(): void
    {
        $path = $this->getPathFor('StaticMethodTypesKeyClassIsNotName.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        $this->assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesKeyIsNotStaticMethodCall(): void
    {
        $path = $this->getPathFor('StaticMethodTypesKeyIsNotStaticMethodCall.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        $this->assertCount(0, $types);
    }

    /**
     * @return void
     */
    public function testSkipsStaticMethodTypesKeyMethodNameIsNotIdentifier(): void
    {
        $path = $this->getPathFor('StaticMethodTypesKeyMethodNameIsNotIdentifier.phpt');

        $this->indexTestFile($this->container, $path);

        $types = $this->container->get('managerRegistry')->getRepository(MetaStaticMethodType::class)->findAll();

        $this->assertCount(0, $types);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/MetaStaticMethodTypesIndexingTest/' . $file;
    }
}
