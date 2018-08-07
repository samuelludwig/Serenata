<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;
use Serenata\Autocompletion\AutocompletionPrefixDeterminerInterface;


/**
 * Provides docblock tag autocompletion suggestions at a specific location in a file.
 */
final class DocblockTagAutocompletionProvider implements AutocompletionProviderInterface
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
    public function provide(AutocompletionProviderContext $context): iterable
    {
        $prefixOverride = $this->autocompletionPrefixDeterminer->determine(
            $context->getTextDocumentItem()->getText(),
            $context->getPosition()
        );

        foreach ($this->getTags() as $tag) {
            yield $this->createSuggestion($tag, $context, $prefixOverride);
        }
    }

    /**
     * @param array                         $tag
     * @param AutocompletionProviderContext $context
     * @param string                        $prefixOverride
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(
        array $tag,
        AutocompletionProviderContext $context,
        string $prefixOverride
    ): AutocompletionSuggestion {
        return new AutocompletionSuggestion(
            $tag['name'],
            SuggestionKind::KEYWORD,
            $tag['insertText'],
            $this->getTextEditForSuggestion($tag, $context, $prefixOverride),
            $tag['name'],
            'PHP docblock tag',
            [
                'returnTypes'  => '',
                'prefix'       => $prefixOverride,
            ],
            [],
            false
        );
    }

    /**
     * Generate a {@see TextEdit} for the suggestion.
     *
     * Some clients automatically determine the prefix to replace on their end (e.g. Atom) and just paste the insertText
     * we send back over this prefix. This prefix sometimes differs from what we see as prefix as the namespace
     * separator (the backslash \) whilst these clients don't. Using a {@see TextEdit} rather than a simple insertText
     * ensures that the entire prefix is replaced along with the insertion.
     *
     * @param array                         $tag
     * @param AutocompletionProviderContext $context
     * @param string                        $prefixOverride
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(
        array $tag,
        AutocompletionProviderContext $context,
        string $prefixOverride
    ): TextEdit {
        return new TextEdit(
            new Range(
                new Position(
                    $context->getPosition()->getLine(),
                    $context->getPosition()->getCharacter() - mb_strlen($prefixOverride)
                ),
                $context->getPosition()
            ),
            $tag['insertText']
        );
    }

    /**
     * @return array[]
     */
    private function getTags(): array
    {
        return [
            ['name' => '@api',            'insertText' => '@api$0'],
            ['name' => '@author',         'insertText' => '@author ${1:name} ${2:[email]}$0'],
            ['name' => '@copyright',      'insertText' => '@copyright ${1:description}$0'],
            ['name' => '@deprecated',     'insertText' => '@deprecated ${1:[vector]} ${2:[description]}$0'],
            ['name' => '@example',        'insertText' => '@example ${1:example}$0'],
            ['name' => '@filesource',     'insertText' => '@filesource$0'],
            ['name' => '@ignore',         'insertText' => '@ignore ${1:[description]}$0'],
            ['name' => '@inheritDoc',     'insertText' => '@inheritDoc$0'],
            ['name' => '@internal',       'insertText' => '@internal ${1:description}$0'],
            ['name' => '@license',        'insertText' => '@license ${1:[url]} ${2:name}$0'],
            ['name' => '@link',           'insertText' => '@link ${1:uri} ${2:[description]}$0'],
            ['name' => '@method',         'insertText' => '@method ${1:type} ${2:name}(${3:[parameter list]})$0'],
            ['name' => '@package',        'insertText' => '@package ${1:package name}$0'],
            ['name' => '@param',          'insertText' => '@param ${1:mixed} \$${2:parameter} ${3:[description]}$0'],
            ['name' => '@property',       'insertText' => '@property ${1:type} ${2:name} ${3:[description]}$0'],
            ['name' => '@property-read',  'insertText' => '@property-read ${1:type} ${2:name} ${3:[description]}$0'],
            ['name' => '@property-write', 'insertText' => '@property-write ${1:type} ${2:name} ${3:[description]}$0'],
            ['name' => '@return',         'insertText' => '@return ${1:type} ${2:[description]}$0'],
            ['name' => '@see',            'insertText' => '@see ${1:URI or FQSEN} ${2:description}$0'],
            ['name' => '@since',          'insertText' => '@since ${1:version} ${2:[description]}$0'],
            ['name' => '@source',         'insertText' => '@source ${1:start line} ${2:number of lines} ${3:[description]}$0'],
            ['name' => '@throws',         'insertText' => '@throws ${1:exception type} ${2:[description]}$0'],
            ['name' => '@todo',           'insertText' => '@todo ${1:description}$0'],
            ['name' => '@uses',           'insertText' => '@uses ${1:FQSEN} ${2:[description]}$0'],
            ['name' => '@var',            'insertText' => '@var ${1:type} ${2:\$${3:[property]} ${4:[description]}}$0'],
            ['name' => '@version',        'insertText' => '@version ${1:vector} ${2:[description]}$0'],
        ];
    }
}
