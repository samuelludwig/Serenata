<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;
use PhpIntegrator\Analysis\Autocompletion\LimitingAutocompletionProvider;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionProviderInterface;

class LimitingAutocompletionProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testLimitsSuggestionsToSpecifiedCount(): void
    {
        $delegate = $this->getMockBuilder(AutocompletionProviderInterface::class)
            ->setMethods(['provide'])
            ->getMock();

        $suggestions = [
            new AutocompletionSuggestion('test1', SuggestionKind::FUNCTION, 'test', 'test', null),
            new AutocompletionSuggestion('test2', SuggestionKind::FUNCTION, 'test', 'test', null)
        ];

        $delegate->method('provide')->willReturn($suggestions);

        $provider = new LimitingAutocompletionProvider($delegate, 1);

        static::assertEquals([
            $suggestions[0],
        ], iterator_to_array($provider->provide("test", 4)));
    }
}
