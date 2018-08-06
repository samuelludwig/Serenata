<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures\File;

use Serenata\Utility\TextEdit;
use Serenata\Utility\PositionEncoding;

/**
 * Provides superglobal autocompletion suggestions at a specific location in a file.
 *
 * @see https://secure.php.net/manual/en/reserved.keywords.php
 * @see https://secure.php.net/manual/en/reserved.other-reserved-words.php
 */
final class SuperglobalAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
    {
        foreach ($this->getSuperGlobals() as $superGlobal) {
            yield $this->createSuggestion($superGlobal, $context);
        }
    }

    /**
     * @param array                         $superGlobal
     * @param AutocompletionProviderContext $context
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(
        array $superGlobal,
        AutocompletionProviderContext $context
    ): AutocompletionSuggestion {
        return new AutocompletionSuggestion(
            $superGlobal['name'],
            SuggestionKind::VARIABLE,
            $superGlobal['name'],
            new TextEdit(
                $context->getPrefixRange(),
                $superGlobal['name']
            ),
            $superGlobal['name'],
            'PHP superglobal',
            [
                'returnTypes'  => $superGlobal['type']
            ],
            [],
            false
        );
    }

    /**
     * @return array
     */
    private function getSuperGlobals(): array
    {
        return [
            ['name' => '$argc',    'type' => 'int'],
            ['name' => '$argv',    'type' => 'array'],
            ['name' => '$GLOBALS', 'type' => 'array'],
            ['name' => '$_SERVER', 'type' => 'array'],
            ['name' => '$_GET',    'type' => 'array'],
            ['name' => '$_POST',   'type' => 'array'],
            ['name' => '$_FILES',  'type' => 'array'],
            ['name' => '$_COOKIE', 'type' => 'array'],
            ['name' => '$_SESSION','type' => 'array'],
            ['name' => '$_REQUEST','type' => 'array'],
            ['name' => '$_ENV',    'type' => 'array']
        ];
    }
}
