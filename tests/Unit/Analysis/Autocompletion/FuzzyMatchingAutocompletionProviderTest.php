<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Autocompletion;

use ReflectionClass;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionProviderInterface;
use PhpIntegrator\Analysis\Autocompletion\FuzzyMatchingAutocompletionProvider;

class FuzzyMatchingAutocompletionProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testFetchesPrefixTakingBoundaryCharactersIntoAccount(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->getMock();

        $provider = new FuzzyMatchingAutocompletionProvider($delegate);

        $reflectionClass = new ReflectionClass(FuzzyMatchingAutocompletionProvider::class);
        $reflectionMethod = $reflectionClass->getMethod('getPrefixAtOffset');
        $reflectionMethod->setAccessible(true);

        static::assertSame('', $reflectionMethod->invoke($provider, 'hello', 0));
        static::assertSame('hell', $reflectionMethod->invoke($provider, 'hello', 4));
        static::assertSame('hello', $reflectionMethod->invoke($provider, 'hello', 5));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel+lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, "hel\nlo", 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, "hel\tlo", 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel(lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel)lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel{lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel}lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel[lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel]lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel+lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel-lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel*lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel/lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel^lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel|lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel&lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel:lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel!lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel@lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel#lo', 6));
        static::assertSame('lo', $reflectionMethod->invoke($provider, 'hel%lo', 6));
    }

    /**
     * @return void
     */
    public function testSortsSuggestionsHigherUpThatRequireFewerInsertions(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $suggestions = [
            new AutocompletionSuggestion('test12', SuggestionKind::FUNCTION, 'test', 'test', null),
            new AutocompletionSuggestion('test1', SuggestionKind::FUNCTION, 'test', 'test', null)
        ];

        $delegate->method('provide')->willReturn($suggestions);

        $provider = new FuzzyMatchingAutocompletionProvider($delegate);

        static::assertEquals([
            $suggestions[0],
            $suggestions[1]
        ], $provider->provide("test", 4));
    }

    /**
     * @return void
     */
    public function testSortsSuggestionsHigherUpThatRequireFewerReplacements(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $suggestions = [
            new AutocompletionSuggestion('tevo', SuggestionKind::FUNCTION, 'test', 'test', null),
            new AutocompletionSuggestion('teso', SuggestionKind::FUNCTION, 'test', 'test', null)
        ];

        $delegate->method('provide')->willReturn($suggestions);

        $provider = new FuzzyMatchingAutocompletionProvider($delegate);

        static::assertEquals([
            $suggestions[1]
        ], $provider->provide("test", 4));
    }

    /**
     * @return void
     */
    public function testSortsSuggestionsHigherUpThatRequireFewerRemovals(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $suggestions = [
            new AutocompletionSuggestion('testos', SuggestionKind::FUNCTION, 'test', 'test', null),
            new AutocompletionSuggestion('testo', SuggestionKind::FUNCTION, 'test', 'test', null)
        ];

        $delegate->method('provide')->willReturn($suggestions);

        $provider = new FuzzyMatchingAutocompletionProvider($delegate);

        static::assertEquals([
            $suggestions[0],
            $suggestions[1]
        ], $provider->provide("test", 4));
    }
}
