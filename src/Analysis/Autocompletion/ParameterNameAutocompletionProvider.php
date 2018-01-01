<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpParser\Node;

use PhpIntegrator\Analysis\NodeAtOffsetLocatorInterface;

use PhpIntegrator\Indexing\Structures\File;

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
     * @param NodeAtOffsetLocatorInterface $nodeAtOffsetLocator
     */
    public function __construct(NodeAtOffsetLocatorInterface $nodeAtOffsetLocator)
    {
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $paramNode = $this->findParamNode($code, $offset);

        if ($paramNode === null) {
            return [];
        }

        return $this->createSuggestionsForParamNode($paramNode);
    }

    /**
     * @param Node\Param $node
     *
     * @return AutocompletionSuggestion[]
     */
    private function createSuggestionsForParamNode(Node\Param $node): array
    {
        if ($node->type === null) {
            return [];
        }

        $typeName = $this->determineTypeNameOfNode($node->type);

        $suggestions = $this->generateSuggestionsForName($typeName);

        $typeNameParts = array_filter(explode('\\', $typeName));

        if (count($typeNameParts) > 1) {
            $lastTypeNamePart = array_pop($typeNameParts);

            $suggestions = array_merge($suggestions, $this->generateSuggestionsForName($lastTypeNamePart));
        }

        return $suggestions;
    }

    /**
     * @param string $name
     *
     * @return AutocompletionSuggestion[]
     */
    private function generateSuggestionsForName(string $name): array
    {
        $suggestions = [];

        // "MyNamespace\Foo" -> "$myNamespaceFoo", "MyClass" -> "$myClass"
        $bestTypeNameApproximation = lcfirst(str_replace('\\', '', $name));

        $suggestions[] = $this->createSuggestion('$' . $bestTypeNameApproximation);

        // "MyNamespace\FooInterface" -> "$myNamespaceFoo", "SomeTrait" -> "Some", "MyClass" -> "$my"
        $bestTypeNameApproximationWithoutLastWord = $this->generateNameWithoutLastWord($bestTypeNameApproximation);

        if ($bestTypeNameApproximationWithoutLastWord !== '') {
            $suggestions[] = $this->createSuggestion('$' . $bestTypeNameApproximationWithoutLastWord);
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
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(string $name): AutocompletionSuggestion
    {
        return new AutocompletionSuggestion(
            $name,
            SuggestionKind::VARIABLE,
            $name,
            null,
            $name,
            null
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
