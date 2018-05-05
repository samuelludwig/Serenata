<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

class ParameterNameAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @return void
     */
    public function testNoType(): void
    {
        $output = $this->provide('UntypedParameter.phpt');

        static::assertEquals([], $output);
    }

    /**
     * @return void
     */
    public function testScalarType(): void
    {
        $output = $this->provide('ScalarParameter.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '$int',
                SuggestionKind::VARIABLE,
                '$int',
                new TextEdit(
                    new Range(new Position(4, 28), new Position(4, 30)),
                    '$int'
                ),
                '$int',
                null,
                [
                    'prefix' => '$p'
                ]
            )
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testUnqualifiedClassType(): void
    {
        $output = $this->provide('UnqualifiedClassParameter.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '$someClasslike',
                SuggestionKind::VARIABLE,
                '$someClasslike',
                new TextEdit(
                    new Range(new Position(4, 38), new Position(4, 40)),
                    '$someClasslike'
                ),
                '$someClasslike',
                null,
                [
                    'prefix' => '$p'
                ]
            ),

            new AutocompletionSuggestion(
                '$some',
                SuggestionKind::VARIABLE,
                '$some',
                new TextEdit(
                    new Range(new Position(4, 38), new Position(4, 40)),
                    '$some'
                ),
                '$some',
                null,
                [
                    'prefix' => '$p'
                ]
            )
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testQualifiedClassType(): void
    {
        $output = $this->provide('QualifiedClassParameter.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '$someClasslike',
                SuggestionKind::VARIABLE,
                '$someClasslike',
                new TextEdit(
                    new Range(new Position(4, 39), new Position(4, 41)),
                    '$someClasslike'
                ),
                '$someClasslike',
                null,
                [
                    'prefix' => '$p'
                ]
            ),

            new AutocompletionSuggestion(
                '$some',
                SuggestionKind::VARIABLE,
                '$some',
                new TextEdit(
                    new Range(new Position(4, 39), new Position(4, 41)),
                    '$some'
                ),
                '$some',
                null,
                [
                    'prefix' => '$p'
                ]
            ),

            new AutocompletionSuggestion(
                '$classlike',
                SuggestionKind::VARIABLE,
                '$classlike',
                new TextEdit(
                    new Range(new Position(4, 39), new Position(4, 41)),
                    '$classlike'
                ),
                '$classlike',
                null,
                [
                    'prefix' => '$p'
                ]
            )
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testFullyQualifiedClassType(): void
    {
        $output = $this->provide('FullyQualifiedClassParameter.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '$someClasslike',
                SuggestionKind::VARIABLE,
                '$someClasslike',
                new TextEdit(
                    new Range(new Position(4, 40), new Position(4, 42)),
                    '$someClasslike'
                ),
                '$someClasslike',
                null,
                [
                    'prefix' => '$p'
                ]
            ),

            new AutocompletionSuggestion(
                '$some',
                SuggestionKind::VARIABLE,
                '$some',
                new TextEdit(
                    new Range(new Position(4, 40), new Position(4, 42)),
                    '$some'
                ),
                '$some',
                null,
                [
                    'prefix' => '$p'
                ]
            ),

            new AutocompletionSuggestion(
                '$classlike',
                SuggestionKind::VARIABLE,
                '$classlike',
                new TextEdit(
                    new Range(new Position(4, 40), new Position(4, 42)),
                    '$classlike'
                ),
                '$classlike',
                null,
                [
                    'prefix' => '$p'
                ]
            )
        ];

        static::assertEquals($suggestions, $output);
    }

    /**
     * @return void
     */
    public function testQualifiedClassTypeWithMultipleWords(): void
    {
        $output = $this->provide('QualifiedClassParameterMultipleWords.phpt');

        $suggestions = [
            new AutocompletionSuggestion(
                '$someClasslikeType',
                SuggestionKind::VARIABLE,
                '$someClasslikeType',
                new TextEdit(
                    new Range(new Position(4, 43), new Position(4, 45)),
                    '$someClasslikeType'
                ),
                '$someClasslikeType',
                null,
                [
                    'prefix' => '$p'
                ]
            ),

            new AutocompletionSuggestion(
                '$someClasslike',
                SuggestionKind::VARIABLE,
                '$someClasslike',
                new TextEdit(
                    new Range(new Position(4, 43), new Position(4, 45)),
                    '$someClasslike'
                ),
                '$someClasslike',
                null,
                [
                    'prefix' => '$p'
                ]
            ),

            new AutocompletionSuggestion(
                '$classlikeType',
                SuggestionKind::VARIABLE,
                '$classlikeType',
                new TextEdit(
                    new Range(new Position(4, 43), new Position(4, 45)),
                    '$classlikeType'
                ),
                '$classlikeType',
                null,
                [
                    'prefix' => '$p'
                ]
            ),

            new AutocompletionSuggestion(
                '$classlike',
                SuggestionKind::VARIABLE,
                '$classlike',
                new TextEdit(
                    new Range(new Position(4, 43), new Position(4, 45)),
                    '$classlike'
                ),
                '$classlike',
                null,
                [
                    'prefix' => '$p'
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
        return 'ParameterNameAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'parameterNameAutocompletionProvider';
    }
}
