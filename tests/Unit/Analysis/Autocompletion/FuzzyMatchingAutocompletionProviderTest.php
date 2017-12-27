<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionProviderInterface;
use PhpIntegrator\Analysis\Autocompletion\FuzzyMatchingAutocompletionProvider;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionPrefixDeterminerInterface;

use PhpIntegrator\Indexing\Structures;

class FuzzyMatchingAutocompletionProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testSortsSuggestionsHigherUpThatRequireFewerInsertions(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $prefixDeterminer = $this->getMockBuilder(AutocompletionPrefixDeterminerInterface::class)
            ->setMethods(['determine'])
            ->getMock();

        $suggestions = [
            new AutocompletionSuggestion('test1', SuggestionKind::FUNCTION, 'test', 'test', null),
            new AutocompletionSuggestion('test12', SuggestionKind::FUNCTION, 'test', 'test', null)
        ];

        $delegate->method('provide')->willReturn($suggestions);
        $prefixDeterminer->method('determine')->willReturn('test');

        $provider = new FuzzyMatchingAutocompletionProvider($delegate, $prefixDeterminer);

        static::assertEquals([
            $suggestions[0],
            $suggestions[1]
        ], $provider->provide($this->getFileStub(), "test", 4));
    }

    /**
     * @return void
     */
    public function testSortsSuggestionsHigherUpThatRequireFewerReplacements(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $prefixDeterminer = $this->getMockBuilder(AutocompletionPrefixDeterminerInterface::class)
            ->setMethods(['determine'])
            ->getMock();

        $suggestions = [
            new AutocompletionSuggestion('tevo', SuggestionKind::FUNCTION, 'test', 'test', null),
            new AutocompletionSuggestion('teso', SuggestionKind::FUNCTION, 'test', 'test', null)
        ];

        $delegate->method('provide')->willReturn($suggestions);
        $prefixDeterminer->method('determine')->willReturn('test');

        $provider = new FuzzyMatchingAutocompletionProvider($delegate, $prefixDeterminer);

        static::assertEquals([
            $suggestions[1],
            $suggestions[0]
        ], $provider->provide($this->getFileStub(), "test", 4));
    }

    /**
     * @return void
     */
    public function testSortsSuggestionsHigherUpThatRequireFewerRemovals(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $prefixDeterminer = $this->getMockBuilder(AutocompletionPrefixDeterminerInterface::class)
            ->setMethods(['determine'])
            ->getMock();

        $suggestions = [
            new AutocompletionSuggestion('testos', SuggestionKind::FUNCTION, 'test', 'test', null),
            new AutocompletionSuggestion('testo', SuggestionKind::FUNCTION, 'test', 'test', null)
        ];

        $delegate->method('provide')->willReturn($suggestions);
        $prefixDeterminer->method('determine')->willReturn('test');

        $provider = new FuzzyMatchingAutocompletionProvider($delegate, $prefixDeterminer);

        static::assertEquals([
            $suggestions[1],
            $suggestions[0]
        ], $provider->provide($this->getFileStub(), "test", 4));
    }

    /**
     * @return void
     */
    public function testDoesNotFailWhenNoSuggestionsArePresent(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $prefixDeterminer = $this->getMockBuilder(AutocompletionPrefixDeterminerInterface::class)
            ->setMethods(['determine'])
            ->getMock();

        $suggestions = [];

        $delegate->method('provide')->willReturn($suggestions);
        $prefixDeterminer->method('determine')->willReturn('test');

        $provider = new FuzzyMatchingAutocompletionProvider($delegate, $prefixDeterminer);

        static::assertEquals([], $provider->provide($this->getFileStub(), "test", 4));
    }

    /**
     * @return void
     */
    public function testDoesNotFailOnEmptyPrefices(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $prefixDeterminer = $this->getMockBuilder(AutocompletionPrefixDeterminerInterface::class)
            ->setMethods(['determine'])
            ->getMock();

        $suggestions = [
            new AutocompletionSuggestion('testos', SuggestionKind::FUNCTION, 'test', 'test', null),
            new AutocompletionSuggestion('testo', SuggestionKind::FUNCTION, 'test', 'test', null)
        ];

        $delegate->method('provide')->willReturn($suggestions);
        $prefixDeterminer->method('determine')->willReturn('test');

        $provider = new FuzzyMatchingAutocompletionProvider($delegate, $prefixDeterminer);

        static::assertEquals([
            $suggestions[1],
            $suggestions[0]
        ], $provider->provide($this->getFileStub(), "", 0));
    }

    /**
     * @return Structures\File
     */
    private function getFileStub(): Structures\File
    {
        return new Structures\File('TestFile.php', new \DateTime(), []);
    }
}
