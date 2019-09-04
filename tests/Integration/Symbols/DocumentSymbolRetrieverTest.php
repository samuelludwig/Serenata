<?php

namespace Serenata\Tests\Integration\Symbols;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Symbols\SymbolKind;
use Serenata\Symbols\SymbolInformation;

use Serenata\Utility\Location;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class DocumentSymbolRetrieverTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testRetrievesConstant(): void
    {
        $filePath = $this->getTestFilePath('Constant.phpt');

        static::assertEquals([
            new SymbolInformation(
                'CONSTANT',
                SymbolKind::CONSTANT,
                false,
                new Location(
                    $filePath,
                    new Range(
                        new Position(2, 6),
                        new Position(2, 18)
                    )
                ),
                null
            ),
        ], $this->getSymbolsForFile($filePath));
    }

    /**
     * @return void
     */
    public function testRetrievesFunction(): void
    {
        $filePath = $this->getTestFilePath('Function.phpt');

        static::assertEquals([
            new SymbolInformation(
                'foo',
                SymbolKind::FUNCTION_,
                false,
                new Location(
                    $filePath,
                    new Range(
                        new Position(2, 0),
                        new Position(5, 1)
                    )
                ),
                null
            ),
        ], $this->getSymbolsForFile($filePath));
    }

    /**
     * @return void
     */
    public function testRetrievesClass(): void
    {
        $filePath = $this->getTestFilePath('Class.phpt');

        static::assertEquals([
            new SymbolInformation(
                'A',
                SymbolKind::CLASS_,
                false,
                new Location(
                    $filePath,
                    new Range(
                        new Position(2, 0),
                        new Position(5, 1)
                    )
                ),
                null
            ),
        ], $this->getSymbolsForFile($filePath));
    }

    /**
     * @return void
     */
    public function testRetrievesInterface(): void
    {
        $filePath = $this->getTestFilePath('Interface.phpt');

        static::assertEquals([
            new SymbolInformation(
                'I',
                SymbolKind::INTERFACE_,
                false,
                new Location(
                    $filePath,
                    new Range(
                        new Position(2, 0),
                        new Position(5, 1)
                    )
                ),
                null
            ),
        ], $this->getSymbolsForFile($filePath));
    }

    /**
     * @return void
     */
    public function testRetrievesTrait(): void
    {
        $filePath = $this->getTestFilePath('Trait.phpt');

        static::assertEquals([
            new SymbolInformation(
                'T',
                SymbolKind::CLASS_,
                false,
                new Location(
                    $filePath,
                    new Range(
                        new Position(2, 0),
                        new Position(5, 1)
                    )
                ),
                null
            ),
        ], $this->getSymbolsForFile($filePath));
    }

    /**
     * @return void
     */
    public function testRetrievesClassConstants(): void
    {
        $filePath = $this->getTestFilePath('ClassConstant.phpt');

        $symbols = $this->getSymbolsForFile($filePath);

        static::assertCount(2, $symbols);
        static::assertEquals(
            new SymbolInformation(
                'CONSTANT',
                SymbolKind::CONSTANT,
                false,
                new Location(
                    $filePath,
                    new Range(
                        new Position(4, 17),
                        new Position(4, 34)
                    )
                ),
                'A'
            ),
            $symbols[1]
        );
    }

    /**
     * @return void
     */
    public function testRetrievesClassMethods(): void
    {
        $filePath = $this->getTestFilePath('Method.phpt');

        $symbols = $this->getSymbolsForFile($filePath);

        static::assertCount(2, $symbols);
        static::assertEquals(
            new SymbolInformation(
                'foo',
                SymbolKind::METHOD,
                false,
                new Location(
                    $filePath,
                    new Range(
                        new Position(4, 4),
                        new Position(7, 5)
                    )
                ),
                'A'
            ),
            $symbols[1]
        );
    }

    /**
     * @return void
     */
    public function testRetrievesClassConstructors(): void
    {
        $filePath = $this->getTestFilePath('ConstructorMethod.phpt');

        $symbols = $this->getSymbolsForFile($filePath);

        static::assertCount(2, $symbols);
        static::assertEquals(
            new SymbolInformation(
                '__construct',
                SymbolKind::CONSTRUCTOR,
                false,
                new Location(
                    $filePath,
                    new Range(
                        new Position(4, 4),
                        new Position(7, 5)
                    )
                ),
                'A'
            ),
            $symbols[1]
        );
    }

    /**
     * @return void
     */
    public function testRetrievesClassProperties(): void
    {
        $filePath = $this->getTestFilePath('Property.phpt');

        $symbols = $this->getSymbolsForFile($filePath);

        static::assertCount(2, $symbols);
        static::assertEquals(
            new SymbolInformation(
                'bar',
                SymbolKind::PROPERTY,
                false,
                new Location(
                    $filePath,
                    new Range(
                        new Position(4, 4),
                        new Position(4, 21)
                    )
                ),
                'A'
            ),
            $symbols[1]
        );
    }

    /**
     * @return void
     */
    public function testSortsSymbolsByLocation(): void
    {
        $filePath = $this->getTestFilePath('MultipleClassMembers.phpt');

        /** @var SymbolInformation[] $symbols */
        $symbols = $this->getSymbolsForFile($filePath);

        static::assertCount(8, $symbols);

        static::assertSame('Class1', $symbols[0]->getName());
        static::assertSame('property1', $symbols[1]->getName());
        static::assertSame('method1', $symbols[2]->getName());
        static::assertSame('CONSTANT1', $symbols[3]->getName());
        static::assertSame('Class2', $symbols[4]->getName());
        static::assertSame('CONSTANT2', $symbols[5]->getName());
        static::assertSame('method2', $symbols[6]->getName());
        static::assertSame('property2', $symbols[7]->getName());
    }

    /**
     * @param string $filePath
     *
     * @return array
     */
    private function getSymbolsForFile(string $filePath): array
    {
        $this->indexTestFile($this->container, $filePath);

        $documentSymbolRetriever = $this->container->get('documentSymbolRetriever');

        return $documentSymbolRetriever->retrieve($this->container->get('storage')->getFileByUri($filePath));
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getTestFilePath(string $fileName): string
    {
        return 'file://' . __DIR__ . '/DocumentSymbolRetrieverTest/' . $fileName;
    }
}
