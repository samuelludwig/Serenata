<?php

namespace PhpIntegrator\Autocompletion;

/**
 * Aids in formatting types for autocompletion suggestions.
 */
final class AutocompletionSuggestionTypeFormatter
{
    /**
     * @param array[] $typeArrayList
     *
     * @return string
     */
    public function format(array $typeArrayList): string
    {
        $shortTypes = [];

        foreach ($typeArrayList as $typeArray) {
            $shortTypes[] = $this->getClassShortNameFromFqcn($typeArray['fqcn']);
        }

        return implode('|', $shortTypes);
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    private function getClassShortNameFromFqcn(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return array_pop($parts);
    }
}
