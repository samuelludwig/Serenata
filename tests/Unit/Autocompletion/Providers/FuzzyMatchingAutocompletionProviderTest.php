<?php

namespace Serenata\Tests\Unit\Autocompletion\Providers;

use PHPUnit\Framework\TestCase;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

use Serenata\Autocompletion\Providers\AutocompletionProviderContext;
use Serenata\Autocompletion\Providers\AutocompletionProviderInterface;
use Serenata\Autocompletion\Providers\FuzzyMatchingAutocompletionProvider;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

use Serenata\Common\Position;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

final class FuzzyMatchingAutocompletionProviderTest extends TestCase
{
    /**
     * @return void
     */
    public function testSortsSuggestionsHigherUpThatHaveBetterScore(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $bestStringApproximationDeterminer = $this->getMockBuilder(BestStringApproximationDeterminerInterface::class)
            ->setMethods(['determine'])
            ->getMock();

        $suggestions = [
            new CompletionItem('test1', CompletionItemKind::FUNCTION, 'test', null, 'test', null),
            new CompletionItem('test12', CompletionItemKind::FUNCTION, 'test', null, 'test', null),
        ];

        $delegate->expects($this->once())->method('provide')->willReturn($suggestions);
        $bestStringApproximationDeterminer->expects($this->once())->method('determine')->willReturn([
            $suggestions[1],
            $suggestions[0],
        ]);

        $provider = new FuzzyMatchingAutocompletionProvider(
            $delegate,
            $bestStringApproximationDeterminer,
            15
        );

        $offset = 4;
        $code = "test";

        $result = $provider->provide(new AutocompletionProviderContext(
            new TextDocumentItem('TestFile.php', $code),
            Position::createFromByteOffset($offset, $code, PositionEncoding::VALUE),
            $code
        ));

        static::assertEquals([
            $suggestions[1],
            $suggestions[0],
        ], $result);
    }
}
