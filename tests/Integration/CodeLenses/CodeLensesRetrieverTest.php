<?php

namespace Serenata\Tests\Integration\CodeLenses;

use Serenata\CodeLenses\CodeLens;
use Serenata\CodeLenses\CodeLensesRetriever;

use Serenata\Commands\OpenTextDocumentCommand;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\TextDocumentContentRegistry;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Utility\TextDocumentItem;

class CodeLensesRetrieverTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testMethodOverride(): void
    {
        $filePath = $this->getTestFilePath('MethodOverride.phpt');

        $this->assertCodeLensesEqual($filePath, [
            new CodeLens(
                new Range(
                    new Position(14, 4),
                    new Position(17, 5)
                ),
                new OpenTextDocumentCommand('Override', $filePath, new Position(6, 4)),
                null
            ),
        ]);
    }

    /**
     * @return void
     */
    public function testMethodImplementation(): void
    {
        $filePath = $this->getTestFilePath('MethodImplementation.phpt');

        $this->assertCodeLensesEqual($filePath, [
            new CodeLens(
                new Range(
                    new Position(11, 4),
                    new Position(14, 5)
                ),
                new OpenTextDocumentCommand('Implementation', $filePath, new Position(6, 4)),
                null
            ),
        ]);
    }

    /**
     * @return void
     */
    public function testPropertyOverride(): void
    {
        $filePath = $this->getTestFilePath('PropertyOverride.phpt');

        $this->assertCodeLensesEqual($filePath, [
            new CodeLens(
                new Range(
                    new Position(11, 4),
                    new Position(11, 19)
                ),
                new OpenTextDocumentCommand('Override', $filePath, new Position(6, 4)),
                null
            ),
        ]);
    }

    /**
     * @param string          $uri
     * @param CodeLens[]|null $codeLenses
     */
    private function assertCodeLensesEqual(string $uri, ?array $codeLenses): void
    {
        $this->indexTestFile($this->container, $uri);

        /** @var CodeLensesRetriever $codeLensesRetriever */
        $codeLensesRetriever = $this->container->get('codeLensesRetriever');

        /** @var TextDocumentContentRegistry $registry */
        $registry = $this->container->get('textDocumentContentRegistry');

        $item = new TextDocumentItem($uri, $registry->get($uri));

        static::assertEquals($codeLenses, $codeLensesRetriever->retrieve($item));
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getTestFilePath(string $fileName): string
    {
        return 'file://' . __DIR__ . '/CodeLensesRetrieverTest/' . $fileName;
    }
}
