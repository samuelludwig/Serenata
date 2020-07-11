<?php

namespace Serenata\Tests\Integration\Symbols;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Symbols\SymbolKind;
use Serenata\Symbols\SymbolInformation;

use Serenata\Utility\Location;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class DocumentSymbolRetrieverTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testRetrievesConstant(): void
    {
        $filePath = $this->getTestFilePath('Constant.phpt');

        self::assertEquals([
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

        self::assertEquals([
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

        self::assertEquals([
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

        self::assertEquals([
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

        self::assertEquals([
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
        $filePath = $this->getTestFilePath('ClassConstant.phpt', false);

        $symbols = $this->getSymbolsForFile($filePath);

        self::assertCount(2, $symbols);
        self::assertEquals(
            new SymbolInformation(
                'CONSTANT',
                SymbolKind::CONSTANT,
                false,
                new Location(
                    $this->normalizePath($filePath),
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
        $filePath = $this->getTestFilePath('Method.phpt', false);

        $symbols = $this->getSymbolsForFile($filePath);

        self::assertCount(2, $symbols);
        self::assertEquals(
            new SymbolInformation(
                'foo',
                SymbolKind::METHOD,
                false,
                new Location(
                    $this->normalizePath($filePath),
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
        $filePath = $this->getTestFilePath('ConstructorMethod.phpt', false);

        $symbols = $this->getSymbolsForFile($filePath);

        self::assertCount(2, $symbols);
        self::assertEquals(
            new SymbolInformation(
                '__construct',
                SymbolKind::CONSTRUCTOR,
                false,
                new Location(
                    $this->normalizePath($filePath),
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
        $filePath = $this->getTestFilePath('Property.phpt', false);

        $symbols = $this->getSymbolsForFile($filePath);

        self::assertCount(2, $symbols);
        self::assertEquals(
            new SymbolInformation(
                'bar',
                SymbolKind::PROPERTY,
                false,
                new Location(
                    $this->normalizePath($filePath),
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
        $filePath = $this->getTestFilePath('MultipleClassMembers.phpt', false);

        /** @var SymbolInformation[] $symbols */
        $symbols = $this->getSymbolsForFile($filePath);

        self::assertCount(8, $symbols);

        self::assertSame('Class1', $symbols[0]->getName());
        self::assertSame('property1', $symbols[1]->getName());
        self::assertSame('method1', $symbols[2]->getName());
        self::assertSame('CONSTANT1', $symbols[3]->getName());
        self::assertSame('Class2', $symbols[4]->getName());
        self::assertSame('CONSTANT2', $symbols[5]->getName());
        self::assertSame('method2', $symbols[6]->getName());
        self::assertSame('property2', $symbols[7]->getName());
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
     * @param bool   $normalize - normalize the file path? Pass false if not using for expectations.
     *
     * @return string
     */
    private function getTestFilePath(string $fileName, bool $normalize = true): string
    {
        $path = 'file://' . __DIR__ . '/DocumentSymbolRetrieverTest/' . $fileName;

        return $normalize ? $this->normalizePath($path) : $path;
    }
}
