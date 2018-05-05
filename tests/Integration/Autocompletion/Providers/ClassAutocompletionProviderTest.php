<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures\ClasslikeTypeNameValue;

use Serenata\Utility\TextEdit;

class ClassAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testRetrievesAllClasslikes(): void
    {
        $output = $this->provide('Class.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '\Foo',
                SuggestionKind::CLASS_,
                'Foo',
                new TextEdit(
                    new Range(new Position(7, 0), new Position(7, 1)),
                    'Foo'
                ),
                'Foo',
                null,
                [
                    'isDeprecated' => false,
                    'returnTypes'  => ClasslikeTypeNameValue::CLASS_,
                    'prefix'       => 'F'
                ]
            )
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
            new AutocompletionSuggestion(
                '\Foo',
                SuggestionKind::CLASS_,
                'Foo',
                new TextEdit(
                    new Range(new Position(10, 0), new Position(10, 1)),
                    'Foo'
                ),
                'Foo',
                null,
                [
                    'isDeprecated' => true,
                    'returnTypes'  => ClasslikeTypeNameValue::CLASS_,
                    'prefix'       => 'F'
                ]
            )
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testSuggestsFullyQualifiedNameIfPrefixStartsWithSlash(): void
    {
        $output = $this->provide('PrefixWithSlash.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '\Foo',
                SuggestionKind::CLASS_,
                '\Foo',
                new TextEdit(
                    new Range(new Position(7, 0), new Position(7, 2)),
                    '\Foo'
                ),
                'Foo',
                null,
                [
                    'isDeprecated' => false,
                    'returnTypes'  => ClasslikeTypeNameValue::CLASS_,
                    'prefix'       => '\F'
                ]
            )
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testIncludesUseStatementImportInSuggestion(): void
    {
        $output = $this->provide('NamespacedClass.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '\Foo\Bar\Baz',
                SuggestionKind::CLASS_,
                'Baz',
                new TextEdit(
                    new Range(new Position(10, 4), new Position(10, 5)),
                    'Baz'
                ),
                'Foo\Bar\Baz',
                null,
                [
                    'isDeprecated' => false,
                    'returnTypes'  => ClasslikeTypeNameValue::CLASS_,
                    'prefix'       => 'F'
                ],
                [
                    new TextEdit(
                        new Range(new Position(10, 0), new Position(10, 0)),
                        "use Foo\Bar\Baz;\n\n"
                    )
                ]
            )
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
        return 'classAutocompletionProvider';
    }
}
