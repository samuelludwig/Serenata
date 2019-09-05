<?php

namespace Serenata\Tests\Integration\Autocompletion\Providers;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

use Serenata\Autocompletion\CompletionItemKind;
use Serenata\Autocompletion\CompletionItem;

final class ParameterNameAutocompletionProviderTest extends AbstractAutocompletionProviderTest
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
            new CompletionItem(
                '$int',
                CompletionItemKind::VARIABLE,
                '$int',
                new TextEdit(
                    new Range(new Position(4, 28), new Position(4, 30)),
                    '$int'
                ),
                '$int',
                null,
                []
            ),
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
            new CompletionItem(
                '$someClasslike',
                CompletionItemKind::VARIABLE,
                '$someClasslike',
                new TextEdit(
                    new Range(new Position(4, 38), new Position(4, 40)),
                    '$someClasslike'
                ),
                '$someClasslike',
                null,
                []
            ),

            new CompletionItem(
                '$some',
                CompletionItemKind::VARIABLE,
                '$some',
                new TextEdit(
                    new Range(new Position(4, 38), new Position(4, 40)),
                    '$some'
                ),
                '$some',
                null,
                []
            ),
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
            new CompletionItem(
                '$someClasslike',
                CompletionItemKind::VARIABLE,
                '$someClasslike',
                new TextEdit(
                    new Range(new Position(4, 39), new Position(4, 41)),
                    '$someClasslike'
                ),
                '$someClasslike',
                null,
                []
            ),

            new CompletionItem(
                '$some',
                CompletionItemKind::VARIABLE,
                '$some',
                new TextEdit(
                    new Range(new Position(4, 39), new Position(4, 41)),
                    '$some'
                ),
                '$some',
                null,
                []
            ),

            new CompletionItem(
                '$classlike',
                CompletionItemKind::VARIABLE,
                '$classlike',
                new TextEdit(
                    new Range(new Position(4, 39), new Position(4, 41)),
                    '$classlike'
                ),
                '$classlike',
                null,
                []
            ),
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
            new CompletionItem(
                '$someClasslike',
                CompletionItemKind::VARIABLE,
                '$someClasslike',
                new TextEdit(
                    new Range(new Position(4, 40), new Position(4, 42)),
                    '$someClasslike'
                ),
                '$someClasslike',
                null,
                []
            ),

            new CompletionItem(
                '$some',
                CompletionItemKind::VARIABLE,
                '$some',
                new TextEdit(
                    new Range(new Position(4, 40), new Position(4, 42)),
                    '$some'
                ),
                '$some',
                null,
                []
            ),

            new CompletionItem(
                '$classlike',
                CompletionItemKind::VARIABLE,
                '$classlike',
                new TextEdit(
                    new Range(new Position(4, 40), new Position(4, 42)),
                    '$classlike'
                ),
                '$classlike',
                null,
                []
            ),
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
            new CompletionItem(
                '$someClasslikeType',
                CompletionItemKind::VARIABLE,
                '$someClasslikeType',
                new TextEdit(
                    new Range(new Position(4, 43), new Position(4, 45)),
                    '$someClasslikeType'
                ),
                '$someClasslikeType',
                null,
                []
            ),

            new CompletionItem(
                '$someClasslike',
                CompletionItemKind::VARIABLE,
                '$someClasslike',
                new TextEdit(
                    new Range(new Position(4, 43), new Position(4, 45)),
                    '$someClasslike'
                ),
                '$someClasslike',
                null,
                []
            ),

            new CompletionItem(
                '$classlikeType',
                CompletionItemKind::VARIABLE,
                '$classlikeType',
                new TextEdit(
                    new Range(new Position(4, 43), new Position(4, 45)),
                    '$classlikeType'
                ),
                '$classlikeType',
                null,
                []
            ),

            new CompletionItem(
                '$classlike',
                CompletionItemKind::VARIABLE,
                '$classlike',
                new TextEdit(
                    new Range(new Position(4, 43), new Position(4, 45)),
                    '$classlike'
                ),
                '$classlike',
                null,
                []
            ),
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
