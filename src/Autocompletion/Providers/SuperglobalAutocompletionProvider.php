<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Indexing\Structures\File;

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
    public function provide(File $file, string $code, int $offset): iterable
    {
        foreach ($this->getSuperGlobals() as $superGlobal) {
            yield $this->createSuggestion($superGlobal);
        }
    }

    /**
     * @param array $superGlobal
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $superGlobal): AutocompletionSuggestion
    {
        return new AutocompletionSuggestion(
            $superGlobal['name'],
            SuggestionKind::VARIABLE,
            $superGlobal['name'],
            null,
            $superGlobal['name'],
            'PHP superglobal',
            [
                'isDeprecated' => false,
                'returnTypes'  => $superGlobal['type']
            ]
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
