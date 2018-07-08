<?php

namespace Serenata\Autocompletion\Providers;

use PhpParser\Node;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;
use Serenata\Utility\SourceCodeHelpers;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;
use Serenata\Autocompletion\AutocompletionPrefixDeterminerInterface;

use Serenata\Indexing\Structures\File;

/**
 * Provides parameter name autocompletion suggestions at a specific location in a file.
 */
final class ParameterNameAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $nodeAtOffsetLocator;

    /**
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @param NodeAtOffsetLocatorInterface            $nodeAtOffsetLocator
     * @param AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
     */
    public function __construct(
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
    ) {
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        $paramNode = $this->findParamNode($code, $offset);

        if ($paramNode === null) {
            return [];
        }

        return $this->createSuggestionsForParamNode($paramNode, $code, $offset, $prefix);
    }

    /**
     * @param Node\Param $node
     * @param string     $code
     * @param int        $offset
     * @param string     $prefix
     *
     * @return AutocompletionSuggestion[]
     */
    private function createSuggestionsForParamNode(Node\Param $node, string $code, int $offset, string $prefix): array
    {
        if ($node->type === null) {
            return [];
        }

        $typeName = $this->determineTypeNameOfNode($node->type);

        $suggestions = $this->generateSuggestionsForName($typeName, $code, $offset, $prefix);

        $typeNameParts = array_filter(explode('\\', $typeName));

        if (count($typeNameParts) > 1) {
            $lastTypeNamePart = array_pop($typeNameParts);

            $suggestions = array_merge($suggestions, $this->generateSuggestionsForName(
                $lastTypeNamePart,
                $code,
                $offset,
                $prefix
            ));
        }

        return $suggestions;
    }

    /**
     * @param string $name
     * @param string $code
     * @param int    $offset
     * @param string $prefix
     *
     * @return AutocompletionSuggestion[]
     */
    private function generateSuggestionsForName(string $name, string $code, int $offset, string $prefix): array
    {
        $suggestions = [];

        // "MyNamespace\Foo" -> "$myNamespaceFoo", "MyClass" -> "$myClass"
        $bestTypeNameApproximation = lcfirst(str_replace('\\', '', $name));

        $suggestions[] = $this->createSuggestion('$' . $bestTypeNameApproximation, $code, $offset, $prefix);

        // "MyNamespace\FooInterface" -> "$myNamespaceFoo", "SomeTrait" -> "Some", "MyClass" -> "$my"
        $bestTypeNameApproximationWithoutLastWord = $this->generateNameWithoutLastWord($bestTypeNameApproximation);

        if ($bestTypeNameApproximationWithoutLastWord !== '') {
            $suggestions[] = $this->createSuggestion(
                '$' . $bestTypeNameApproximationWithoutLastWord,
                $code,
                $offset,
                $prefix
            );
        }

        return $suggestions;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function generateNameWithoutLastWord(string $name): string
    {
        $words = preg_split(
            '/([A-Z][^A-Z]+)/',
            $name,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );

        if (empty($words)) {
            return '';
        }

        array_pop($words);

        return implode('', $words);
    }

    /**
     * @param Node $node
     *
     * @return string|null
     */
    private function determineTypeNameOfNode(Node $node): ?string
    {
        if ($node instanceof Node\NullableType) {
            return $this->determineTypeNameOfNode($node->type);
        } elseif ($node instanceof Node\Identifier) {
            return $node->toString();
        } elseif ($node instanceof Node\Name) {
            return $node->toString();
        }

        return null;
    }

    /**
     * @param string $name
     * @param string $code
     * @param int    $offset
     * @param string $prefix
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(string $name, string $code, int $offset, string $prefix): AutocompletionSuggestion
    {
        return new AutocompletionSuggestion(
            $name,
            SuggestionKind::VARIABLE,
            $name,
            $this->getTextEditForSuggestion($name, $code, $offset, $prefix),
            $name,
            null,
            [
                'prefix' => $prefix
            ]
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
     * @param string $name
     * @param string $code
     * @param int    $offset
     * @param string $prefix
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(string $name, string $code, int $offset, string $prefix): TextEdit
    {
        $line = SourceCodeHelpers::calculateLineByOffset($code, $offset) - 1;
        $character = SourceCodeHelpers::getCharacterOnLineFromByteOffset($offset, $code);

        return new TextEdit(
            new Range(new Position($line, $character - mb_strlen($prefix)), new Position($line, $character)),
            $name
        );
    }

    /**
     * @param string $code
     * @param int    $offset
     *
     * @return Node\Param|null
     */
    private function findParamNode(string $code, int $offset): ?Node\Param
    {
        $nodeResult = $this->nodeAtOffsetLocator->locate($code, $offset - 1);

        $node = $nodeResult->getNode();

        if ($node instanceof Node\Expr\Variable) {
            $parent = $node->getAttribute('parent', false);

            return $parent instanceof Node\Param ? $parent : null;
        }

        return $node instanceof Node\Param ? $node : null;
    }
}
