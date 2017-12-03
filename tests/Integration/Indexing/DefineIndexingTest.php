<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Indexing\Structures;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class DefineIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleDefine(): void
    {
        $define = $this->indexDefine('SimpleDefine.phpt');

        static::assertSame('DEFINE', $define->getName());
        static::assertSame('\DEFINE', $define->getFqcn());
        static::assertSame($this->getPathFor('SimpleDefine.phpt'), $define->getFile()->getPath());
        static::assertSame(3, $define->getStartLine());
        static::assertSame(3, $define->getEndLine());
        static::assertSame("'VALUE'", $define->getDefaultValue());
        static::assertFalse($define->getIsDeprecated());
        static::assertFalse($define->getHasDocblock());
        static::assertNull($define->getShortDescription());
        static::assertNull($define->getLongDescription());
        static::assertNull($define->getTypeDescription());
        static::assertCount(1, $define->getTypes());
        static::assertSame('string', $define->getTypes()[0]->getType());
        static::assertSame('string', $define->getTypes()[0]->getFqcn());
    }

    /**
     * @return void
     */
    public function testDefineFqcnWithNamespace(): void
    {
        $constant = $this->indexDefine('DefineFqcnWithNamespace.phpt');

        static::assertSame('\N\DEFINE', $constant->getFqcn());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $constants = $this->container->get('managerRegistry')->getRepository(Structures\Constant::class)->findAll();

            static::assertCount(1, $constants);
            static::assertSame('\DEFINE', $constants[0]->getFqcn());

            return str_replace('DEFINE', 'DEFINE2', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $constants = $this->container->get('managerRegistry')->getRepository(Structures\Constant::class)->findAll();

            static::assertCount(1, $constants);
            static::assertSame('\DEFINE2', $constants[0]->getFqcn());
        };

        $path = $this->getPathFor('DefineChanges.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Constant
     */
    private function indexDefine(string $file): Structures\Constant
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $constants = $this->container->get('managerRegistry')->getRepository(Structures\Constant::class)->findAll();

        static::assertCount(1, $constants);

        return $constants[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return __DIR__ . '/DefineIndexingTest/' . $file;
    }
}
