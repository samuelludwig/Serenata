<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use PhpParser\Node;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class FileIndexerTest extends AbstractIntegrationTest
{
    /**
     * @param string $file
     *
     * @return ContainerBuilder
     */
    protected function index(string $file): ContainerBuilder
    {
        $path = $this->getPathFor($file);

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path);

        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        $container->get('fileIndexer')->index($path, $code);

        return $container;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/FileIndexerTest/' . $file;
    }

    /**
     * @return void
     */
    public function testStaticMethodTypes(): void
    {
        $container = $this->index('StaticMethodTypes.phpt');

        $types = $container->get('metadataProvider')->getMetaStaticMethodTypesFor('\A\Foo', 'get');

        $this->assertCount(2, $types);

        $this->assertEquals(0, $types[0]->getArgumentIndex());
        $this->assertEquals('bar', $types[0]->getValue());
        $this->assertEquals(Node\Scalar\String_::class, $types[0]->getValueNodeType());
        $this->assertEquals('\B\Bar', $types[0]->getReturnType());

        $this->assertEquals(0, $types[1]->getArgumentIndex());
        $this->assertEquals('car', $types[1]->getValue());
        $this->assertEquals(Node\Scalar\String_::class, $types[1]->getValueNodeType());
        $this->assertEquals('\B\Car', $types[1]->getReturnType());
    }
}
