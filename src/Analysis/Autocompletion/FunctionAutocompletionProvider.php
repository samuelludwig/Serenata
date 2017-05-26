<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpIntegrator\Analysis\FunctionListProviderInterface;

/**
 * Provides function autocompletion suggestions at a specific location in a file.
 */
class FunctionAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var FunctionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @param FunctionListProviderInterface $functionListProvider
     */
    public function __construct(FunctionListProviderInterface $functionListProvider)
    {
        $this->functionListProvider = $functionListProvider;
    }

    /**
     * @inheritDoc
     */
    public function provide(string $code, int $offset): array
    {
        return $this->getFunctionSuggestions();
    }

    /**
     * @return AutocompletionSuggestion[]
     */
    protected function getFunctionSuggestions(): array
    {
        $suggestions = [];

        foreach ($this->functionListProvider->getAll() as $globalFunction) {
            $suggestions[] = $this->getFunctionSuggestionFromSuggestion($globalFunction);
        }

        return $suggestions;
    }

    /**
     * @param array $globalFunction
     *
     * @return AutocompletionSuggestion
     */
    protected function getFunctionSuggestionFromSuggestion(array $globalFunction): AutocompletionSuggestion
    {
        $insertText = $globalFunction['name'];
        $placeCursorBetweenParentheses = !empty($globalFunction['parameters']);


        if (true) {
            // TODO: If insertion position is followed by opening paraenthesis, don't add paraentheses at all.
            $insertText .= '()';
        }

        return new AutocompletionSuggestion(
            $globalFunction['name'],
            SuggestionKind::FUNCTION,
            $insertText,
            $this->getFunctionLabel($globalFunction),
            $this->getFunctionDocumentation($globalFunction),
            [
                'isDeprecated'                  => $globalFunction['isDeprecated'],
                'protectionLevel'               => null, // TODO: For leftLabel
                'declaringStructure'            => null,
                'url'                           => $this->getFunctionUrl($globalFunction),
                'returnTypes'                   => $this->getFunctionReturnTypes($globalFunction),
                'placeCursorBetweenParentheses' => $placeCursorBetweenParentheses
            ]
        );
    }

    /**
     * @param array $function
     *
     * @return string
     */
    protected function getFunctionLabel(array $function): string
    {
        $body = '(';

        $isInOptionalList = false;

        foreach ($function['parameters'] as $index => $param) {
            $description = '';

            if ($param['isOptional'] && !$isInOptionalList) {
                $description .= '[';
            }

            if ($index > 0) {
                $description .= ', ';
            }

            if ($param['isVariadic']) {
                $description .= '...';
            }

            if ($param['isReference']) {
                $description .= '&';
            }

            $description .= '$' . $param['name'];

            if ($param['defaultValue']) {
                $description .= ' = ' . $param['defaultValue'];
            }

            if ($param['isOptional'] && $index === (count($function['parameters']) - 1)) {
                $description .= ']';
            }

            $isInOptionalList = $param['isOptional'];

            $body .= $description;
        }

        $body .= ')';

        return $body;
    }

    /**
     * @param array $function
     *
     * @return string|null
     */
    protected function getFunctionDocumentation(array $function): ?string
    {
        if ($function['shortDescription']) {
            return $function['shortDescription'];
        } elseif ($function['isBuiltin']) {
            return 'Built-in PHP function.';
        }

        return null;
    }

    /**
     * @param array $function
     *
     * @return string|null
     */
    protected function getFunctionProtectionLevel(array $function): ?string
    {
        if ($function['isPublic']) {
            return 'public';
        } elseif ($function['isProtected']) {
            return 'protected';
        } elseif ($function['isPrivate']) {
            return 'private';
        }

        return null;
    }

    /**
     * @param array $function
     *
     * @return string|null
     */
    protected function getFunctionUrl(array $function): ?string
    {
        if ($function['isBuiltin']) {
            return DocumentationBaseUrl::FUNCTIONS . $function['name'];
        }

        return null;
    }

    /**
     * @param array $function
     *
     * @return string
     */
    protected function getFunctionReturnTypes(array $function): string
    {
        $typeNames = $this->getShortFunctionReturnTypes($function);

        return implode('|', $typeNames);
    }

    /**
     * @param array $function
     *
     * @return string[]
     */
    protected function getShortFunctionReturnTypes(array $function): array
    {
        $shortTypes = [];

        foreach ($function['returnTypes'] as $type) {
            $shortTypes[] = $this->getClassShortNameFromFqcn($type['fqcn']);
        }

        return $shortTypes;
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    protected function getClassShortNameFromFqcn(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return array_pop($parts);
    }
}
