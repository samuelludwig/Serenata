<?php

namespace Serenata\Autocompletion;

/**
 * Aids in formatting types for autocompletion suggestions.
 */
final class CompletionItemDetailFormatter
{
    /**
     * @param string|null $declaringStructureFqcn
     * @param string|null $accessModifierName     See also {@see AccessModifierNameValue}.
     * @param array[]     $typeArrayList
     *
     * @return string
     */
    public function format(?string $declaringStructureFqcn, ?string $accessModifierName, array $typeArrayList): string
    {
        return implode(' — ', array_filter([
            $this->formatTypes($typeArrayList),
            $accessModifierName !== null ? $this->formatAccessModifier($accessModifierName) : null,
            $declaringStructureFqcn !== null ? $this->formatDeclaringStructure($declaringStructureFqcn) : null,
        ]));
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    private function formatDeclaringStructure(string $fqcn): string
    {
        return array_slice(explode('\\', $fqcn), -1)[0];
    }

    /**
     * @param string $accessModifierName
     *
     * @return string
     */
    private function formatAccessModifier(string $accessModifierName): string
    {
        return $accessModifierName;
    }

    /**
     * @param array[] $typeArrayList
     *
     * @return string
     */
    private function formatTypes(array $typeArrayList): string
    {
        $shortTypes = [];

        foreach ($typeArrayList as $typeArray) {
            $shortTypes[] = $this->getClassShortNameFromFqcn($typeArray['type']);
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
