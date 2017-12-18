<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpIntegrator\Analysis\ConstantListProviderInterface;

use PhpIntegrator\Indexing\Structures\File;

/**
 * Provides constant autocompletion suggestions at a specific location in a file.
 */
final class ConstantAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var ConstantListProviderInterface
     */
    private $constantListProvider;

    /**
     * @param ConstantListProviderInterface $constantListProvider
     */
    public function __construct(ConstantListProviderInterface $constantListProvider)
    {
        $this->constantListProvider = $constantListProvider;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        foreach ($this->constantListProvider->getAll() as $constant) {
            yield $this->createSuggestion($constant);
        }
    }

    /**
     * @param array $constant
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $constant): AutocompletionSuggestion
    {
        return new AutocompletionSuggestion(
            $constant['name'],
            SuggestionKind::CONSTANT,
            $constant['name'],
            $constant['name'],
            $constant['shortDescription'],
            [
                'isDeprecated' => $constant['isDeprecated'],
                'returnTypes'  => $this->createReturnTypes($constant)
            ]
        );
    }

    /**
     * @param array $constant
     *
     * @return string
     */
    private function createReturnTypes(array $constant): string
    {
        $typeNames = $this->getShortReturnTypes($constant);

        return implode('|', $typeNames);
    }

    /**
     * @param array $constant
     *
     * @return string[]
     */
    private function getShortReturnTypes(array $constant): array
    {
        $shortTypes = [];

        foreach ($constant['types'] as $type) {
            $shortTypes[] = $this->getClassShortNameFromFqcn($type['fqcn']);
        }

        return $shortTypes;
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
