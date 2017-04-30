<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\MetaFileIndexer;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use PhpParser\Node;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class MetaFileIndexerTest extends AbstractIntegrationTest
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
        return __DIR__ . '/MetaFileIndexerTest/' . $file;
    }

    /**
     * @return void
     */
    public function testStaticMethodTypes(): void
    {
        $container = $this->index('StaticMethodTypes.phpt');

        $types = $container->get('indexDatabase')->getMetaStaticMethodTypesFor('\A\Foo', 'get');

        $this->assertEquals([
            [
                'argument_index'  => 0,
                'value'           => 'bar',
                'value_node_type' => Node\Scalar\String_::class,
                'return_type'     => '\B\Bar'
            ],

            [
                'argument_index'  => 0,
                'value'           => 'car',
                'value_node_type' => Node\Scalar\String_::class,
                'return_type'     => '\B\Car'
            ]
        ], $types);
    }
}
