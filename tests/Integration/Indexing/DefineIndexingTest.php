<?php

namespace Serenata\Tests\Integration\Indexing;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DefineIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleDefine(): void
    {
        $define = $this->indexDefine('SimpleDefine.phpt');

        self::assertSame('DEFINE', $define->getName());
        self::assertSame('\DEFINE', $define->getFqcn());
        self::assertSame($this->normalizePath($this->getPathFor('SimpleDefine.phpt')), $define->getFile()->getUri());
        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(2, 25)
            ),
            $define->getRange()
        );
        self::assertSame("'VALUE'", $define->getDefaultValue());
        self::assertFalse($define->getIsDeprecated());
        self::assertFalse($define->getHasDocblock());
        self::assertNull($define->getShortDescription());
        self::assertNull($define->getLongDescription());
        self::assertNull($define->getTypeDescription());
        self::assertSame('string', (string) $define->getType());
    }

    /**
     * @return void
     */
    public function testDefineFqcnWithNamespace(): void
    {
        $constant = $this->indexDefine('DefineFqcnWithNamespace.phpt');

        self::assertSame('\N\DEFINE', $constant->getFqcn());
    }

    /**
     * @return void
     */
    public function testChangesArePickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source): string {
            $constants = $this->container->get('managerRegistry')->getRepository(Structures\Constant::class)->findAll();

            self::assertCount(1, $constants);
            self::assertSame('\DEFINE', $constants[0]->getFqcn());

            return str_replace('DEFINE', 'DEFINE2', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source): void {
            $constants = $this->container->get('managerRegistry')->getRepository(Structures\Constant::class)->findAll();

            self::assertCount(1, $constants);
            self::assertSame('\DEFINE2', $constants[0]->getFqcn());
        };

        $path = $this->getPathFor('DefineChanges.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
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

        self::assertCount(1, $constants);

        return $constants[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/DefineIndexingTest/' . $file;
    }
}
