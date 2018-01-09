<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion;

use PhpIntegrator\Autocompletion\SuggestionKind;
use PhpIntegrator\Autocompletion\AutocompletionSuggestion;

use PhpIntegrator\Common\Range;
use PhpIntegrator\Common\Position;

use PhpIntegrator\Indexing\Structures\ClasslikeTypeNameValue;

use PhpIntegrator\Utility\TextEdit;

class ClasslikeAutocompletionProviderTest extends AbstractAutocompletionProviderTest
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
                    new Range(new Position(7, 0), new Position(7, 0)),
                    'Foo'
                ),
                'Foo',
                null,
                [
                    'isDeprecated' => false,
                    'returnTypes'  => ClasslikeTypeNameValue::CLASS_,
                    'prefix'       => ''
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
                    new Range(new Position(10, 0), new Position(10, 0)),
                    'Foo'
                ),
                'Foo',
                null,
                [
                    'isDeprecated' => true,
                    'returnTypes'  => ClasslikeTypeNameValue::CLASS_,
                    'prefix'       => ''
                ]
            )
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
            new AutocompletionSuggestion(
                '\Foo',
                SuggestionKind::MIXIN,
                'Foo',
                new TextEdit(
                    new Range(new Position(7, 0), new Position(7, 0)),
                    'Foo'
                ),
                'Foo',
                null,
                [
                    'isDeprecated' => false,
                    'returnTypes'  => ClasslikeTypeNameValue::TRAIT_,
                    'prefix'       => ''
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
                    new Range(new Position(10, 4), new Position(10, 4)),
                    'Baz'
                ),
                'Foo\Bar\Baz',
                null,
                [
                    'isDeprecated' => false,
                    'returnTypes'  => ClasslikeTypeNameValue::CLASS_,
                    'prefix'       => ''
                ],
                [
                    new TextEdit(
                        new Range(new Position(10, 0), new Position(10, 0)),
                        "use Foo\Bar\Baz;\n"
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
        return 'classlikeAutocompletionProvider';
    }
}
