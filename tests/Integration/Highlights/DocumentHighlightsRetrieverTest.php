<?php

namespace Serenata\Tests\Integration\Highlights;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Highlights\DocumentHighlight;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Utility\TextDocumentItem;

final class DocumentHighlightsRetrieverTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testUnqualifiedConstant(): void
    {
        $filePath = $this->getTestFilePath('UnqualifiedConstant.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 4, 8, 15, [
            new DocumentHighlight(
                new Range(
                    new Position(2, 6),
                    new Position(2, 14)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(4, 8),
                    new Position(4, 16)
                ),
                null
            ),
        ]);
    }

    /**
     * @return void
     */
    public function testQualifiedConstant(): void
    {
        $filePath = $this->getTestFilePath('QualifiedConstant.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 5, 12, 19, [
            new DocumentHighlight(
                new Range(
                    new Position(3, 10),
                    new Position(3, 18)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(5, 12),
                    new Position(5, 20)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(11, 13),
                    new Position(11, 25)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(15, 13),
                    new Position(15, 26)
                ),
                null
            ),
        ]);
    }


    /**
     * @return void
     */
    public function testUnqualifiedFunction(): void
    {
        $filePath = $this->getTestFilePath('UnqualifiedFunction.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 4, 8, 10, [
            new DocumentHighlight(
                new Range(
                    new Position(2, 9),
                    new Position(2, 12)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(4, 8),
                    new Position(4, 11)
                ),
                null
            ),
        ]);
    }

    /**
     * @return void
     */
    public function testQualifiedFunction(): void
    {
        $filePath = $this->getTestFilePath('QualifiedFunction.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 5, 12, 14, [
            new DocumentHighlight(
                new Range(
                    new Position(3, 13),
                    new Position(3, 16)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(5, 12),
                    new Position(5, 15)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(11, 13),
                    new Position(11, 20)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(15, 13),
                    new Position(15, 21)
                ),
                null
            ),
        ]);
    }

    /**
     * @return void
     */
    public function testUnqualifiedClass(): void
    {
        $filePath = $this->getTestFilePath('UnqualifiedClass.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 7, 11, 13, [
            new DocumentHighlight(
                new Range(
                    new Position(2, 6),
                    new Position(2, 9)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(7, 11),
                    new Position(7, 14)
                ),
                null
            ),
        ]);
    }

    /**
     * @return void
     */
    public function testQualifiedClass(): void
    {
        $filePath = $this->getTestFilePath('QualifiedClass.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 5, 16, 18, [
            new DocumentHighlight(
                new Range(
                    new Position(3, 10),
                    new Position(3, 13)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(5, 16),
                    new Position(5, 19)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(11, 17),
                    new Position(11, 24)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(15, 17),
                    new Position(15, 25)
                ),
                null
            ),
        ]);
    }

    /**
     * @return void
     */
    public function testConstantInUnqualifiedClassConstant(): void
    {
        $filePath = $this->getTestFilePath('UnqualifiedClassConstant.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 7, 12, 14, [
            new DocumentHighlight(
                new Range(
                    new Position(4, 17),
                    new Position(4, 20)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(7, 12),
                    new Position(7, 15)
                ),
                null
            ),
        ]);
    }

    /**
     * @return void
     */
    public function testClassInUnqualifiedClassConstant(): void
    {
        $filePath = $this->getTestFilePath('UnqualifiedClassConstant.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 7, 7, 9, [
            new DocumentHighlight(
                new Range(
                    new Position(2, 6),
                    new Position(2, 9)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(7, 7),
                    new Position(7, 10)
                ),
                null
            ),
        ]);
    }

    // TODO: Should pass once class constants are properly supported.
    // /**
    //  * @return void
    //  */
    // public function testConstantInQualifiedClassConstant(): void
    // {
    //     $filePath = $this->getTestFilePath('QualifiedClassConstant.phpt');
    //
    //     $this->assertDocumentHighlightsEqual($filePath, 7, 17, 19, [
    //         new DocumentHighlight(
    //             new Range(
    //                 new Position(4, 21),
    //                 new Position(4, 24)
    //             ),
    //             null
    //         ),
    //         new DocumentHighlight(
    //             new Range(
    //                 new Position(7, 17),
    //                 new Position(7, 20)
    //             ),
    //             null
    //         ),
    //         new DocumentHighlight(
    //             new Range(
    //                 new Position(11, 17),
    //                 new Position(11, 24)
    //             ),
    //             null
    //         ),
    //         new DocumentHighlight(
    //             new Range(
    //                 new Position(15, 17),
    //                 new Position(15, 25)
    //             ),
    //             null
    //         ),
    //     ]);
    // }

    /**
     * @return void
     */
    public function testClassInQualifiedClassConstant(): void
    {
        $filePath = $this->getTestFilePath('QualifiedClassConstant.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 7, 12, 14, [
            new DocumentHighlight(
                new Range(
                    new Position(3, 10),
                    new Position(3, 13)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(7, 12),
                    new Position(7, 15)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(13, 13),
                    new Position(13, 20)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(17, 13),
                    new Position(17, 21)
                ),
                null
            ),
        ]);
    }

    /**
     * @return void
     */
    public function testUnqualifiedClassMatchesImport(): void
    {
        $filePath = $this->getTestFilePath('UnqualifiedClassWithImport.phpt');

        $this->assertDocumentHighlightsEqual($filePath, 6, 11, 13, [
            new DocumentHighlight(
                new Range(
                    new Position(4, 4),
                    new Position(4, 11)
                ),
                null
            ),
            new DocumentHighlight(
                new Range(
                    new Position(6, 11),
                    new Position(6, 14)
                ),
                null
            ),
        ]);
    }

    /**
     * @param string                   $uri
     * @param int                      $line
     * @param int                      $startCharacter
     * @param int                      $endCharacter
     * @param DocumentHighlight[]|null $highlights
     */
    private function assertDocumentHighlightsEqual(
        string $uri,
        int $line,
        int $startCharacter,
        int $endCharacter,
        ?array $highlights
    ): void {
        $i = $startCharacter;

        $this->indexTestFile($this->container, $uri);

        $documentHighlightsRetriever = $this->container->get('documentHighlightsRetriever');

        $item = new TextDocumentItem($uri, $this->container->get('textDocumentContentRegistry')->get($uri));

        while ($i <= $endCharacter) {
            static::assertEquals(
                $highlights,
                $documentHighlightsRetriever->retrieve($item, new Position($line, $i)),
                'Range must include [' . $line . ',' . $i . ']'
            );

            ++$i;
        }

        // Assert that the range doesn't extend longer than it should.
        $resultBeforeRange = $documentHighlightsRetriever->retrieve($item, new Position($line, $startCharacter - 1));

        static::assertTrue(
            $resultBeforeRange === null || $resultBeforeRange !== $highlights,
            "Range does not start exactly at position [{$line},{$startCharacter}], but seems to continue before it"
        );

        $resultAfterRange = $documentHighlightsRetriever->retrieve($item, new Position($line, $endCharacter + 1));

        static::assertTrue(
            $resultAfterRange === null || $resultAfterRange !== $highlights,
            "Range does not end exactly at position [{$line},{$endCharacter}], but seems to continue after it"
        );
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getTestFilePath(string $fileName): string
    {
        return 'file://' . __DIR__ . '/DocumentHighlightsRetrieverTest/' . $fileName;
    }
}
