<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;
use Serenata\Utility\PositionEncoding;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;
use Serenata\Autocompletion\AutocompletionPrefixDeterminerInterface;

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
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @param AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
     */
    public function __construct(AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer)
    {
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        foreach ($this->getSuperGlobals() as $superGlobal) {
            yield $this->createSuggestion($superGlobal, $code, $offset, $prefix);
        }
    }

    /**
     * @param array  $superGlobal
     * @param string $code
     * @param int    $offset
     * @param string $prefix
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(
        array $superGlobal,
        string $code,
        int $offset,
        string $prefix
    ): AutocompletionSuggestion {
        $endPosition = Position::createFromByteOffset($offset, $code, PositionEncoding::VALUE);

        $textEdit = new TextEdit(
            new Range(
                new Position($endPosition->getLine(), $endPosition->getCharacter() - mb_strlen($prefix)),
                $endPosition
            ),
            $superGlobal['name']
        );

        return new AutocompletionSuggestion(
            $superGlobal['name'],
            SuggestionKind::VARIABLE,
            $superGlobal['name'],
            $textEdit,
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
