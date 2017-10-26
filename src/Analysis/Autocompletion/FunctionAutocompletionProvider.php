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
        return $this->getSuggestions();
    }

    /**
     * @return AutocompletionSuggestion[]
     */
    protected function getSuggestions(): array
    {
        $suggestions = [];

        foreach ($this->functionListProvider->getAll() as $function) {
            $suggestions[] = $this->createSuggestion($function);
        }

        return $suggestions;
    }

    /**
     * @param array $function
     *
     * @return AutocompletionSuggestion
     */
    protected function createSuggestion(array $function): AutocompletionSuggestion
    {
        $insertText = $function['name'];
        $placeCursorBetweenParentheses = !empty($function['parameters']);


        if (true) {
            // TODO: If insertion position is followed by opening paraenthesis, don't add paraentheses at all.
            $insertText .= '()';
        }

        return new AutocompletionSuggestion(
            $function['name'],
            SuggestionKind::FUNCTION,
            $insertText,
            $this->createLabel($function),
            $function['shortDescription'],
            [
                'isDeprecated'                  => $function['isDeprecated'],
                'protectionLevel'               => null, // TODO: For leftLabel
                'declaringStructure'            => null,
                'url'                           => null,
                'returnTypes'                   => $this->createReturnTypes($function),
                'placeCursorBetweenParentheses' => $placeCursorBetweenParentheses
            ]
        );
    }

    /**
     * @param array $function
     *
     * @return string
     */
    protected function createLabel(array $function): string
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

        return $function['name'] . $body;
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
     * @return string
     */
    protected function createReturnTypes(array $function): string
    {
        $typeNames = $this->getShortReturnTypes($function);

        return implode('|', $typeNames);
    }

    /**
     * @param array $function
     *
     * @return string[]
     */
    protected function getShortReturnTypes(array $function): array
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
