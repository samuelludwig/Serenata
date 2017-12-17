<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\SuggestionKind;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionSuggestion;

use PhpIntegrator\Indexing\Structures\ClasslikeTypeNameValue;

class ClasslikeAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllClasslikes(): void
    {
        $output = $this->provide('Class.phpt');

        $suggestions = [
            new AutocompletionSuggestion('Foo', SuggestionKind::CLASS_, '\Foo', 'Foo', null, [
                'isDeprecated' => false,
                'returnTypes'  => ClasslikeTypeNameValue::CLASS_
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testMarksDeprecatedClasslikeAsDeprecated(): void
    {
        $output = $this->provide('DeprecatedClass.phpt');

        $suggestions = [
            new AutocompletionSuggestion('Foo', SuggestionKind::CLASS_, '\Foo', 'Foo', null, [
                'isDeprecated' => true,
                'returnTypes'  => ClasslikeTypeNameValue::CLASS_
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testUsesMixinTypeForTraits(): void
    {
        $output = $this->provide('Trait.phpt');

        $suggestions = [
            new AutocompletionSuggestion('Foo', SuggestionKind::MIXIN, '\Foo', 'Foo', null, [
                'isDeprecated' => false,
                'returnTypes'  => ClasslikeTypeNameValue::TRAIT_
            ])
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'ClasslikeAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'classlikeAutocompletionProvider';
    }
}
