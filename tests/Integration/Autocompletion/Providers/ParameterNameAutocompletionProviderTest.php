<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion\Providers;

use PhpIntegrator\Autocompletion\SuggestionKind;
use PhpIntegrator\Autocompletion\AutocompletionSuggestion;

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
            new AutocompletionSuggestion('$int', SuggestionKind::VARIABLE, '$int', null, '$int', null)
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
                null,
                '$someClasslike',
                null
            ),

            new AutocompletionSuggestion(
                '$some',
                SuggestionKind::VARIABLE,
                '$some',
                null,
                '$some',
                null
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
                null,
                '$someClasslike',
                null
            ),

            new AutocompletionSuggestion(
                '$some',
                SuggestionKind::VARIABLE,
                '$some',
                null,
                '$some',
                null
            ),

            new AutocompletionSuggestion(
                '$classlike',
                SuggestionKind::VARIABLE,
                '$classlike',
                null,
                '$classlike',
                null
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
                null,
                '$someClasslike',
                null
            ),

            new AutocompletionSuggestion(
                '$some',
                SuggestionKind::VARIABLE,
                '$some',
                null,
                '$some',
                null
            ),

            new AutocompletionSuggestion(
                '$classlike',
                SuggestionKind::VARIABLE,
                '$classlike',
                null,
                '$classlike',
                null
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
                null,
                '$someClasslikeType',
                null
            ),

            new AutocompletionSuggestion(
                '$someClasslike',
                SuggestionKind::VARIABLE,
                '$someClasslike',
                null,
                '$someClasslike',
                null
            ),

            new AutocompletionSuggestion(
                '$classlikeType',
                SuggestionKind::VARIABLE,
                '$classlikeType',
                null,
                '$classlikeType',
                null
            ),

            new AutocompletionSuggestion(
                '$classlike',
                SuggestionKind::VARIABLE,
                '$classlike',
                null,
                '$classlike',
                null
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
