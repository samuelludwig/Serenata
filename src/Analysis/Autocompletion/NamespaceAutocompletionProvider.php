<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpIntegrator\Analysis\NamespaceListProviderInterface;

use PhpIntegrator\Indexing\Structures\File;

/**
 * Provides namespace autocompletion suggestions at a specific location in a file.
 */
final class NamespaceAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var NamespaceListProviderInterface
     */
    private $namespaceListProvider;

    /**
     * @param NamespaceListProviderInterface $namespaceListProvider
     */
    public function __construct(NamespaceListProviderInterface $namespaceListProvider)
    {
        $this->namespaceListProvider = $namespaceListProvider;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        foreach ($this->namespaceListProvider->getAll() as $namespace) {
            if ($namespace['name'] !== null) {
                yield $this->createSuggestion($namespace);
            }
        }
    }

    /**
     * @param array $namespace
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $namespace): AutocompletionSuggestion
    {
        $fqcnWithoutLeadingSlash = $namespace['name'];

        if ($fqcnWithoutLeadingSlash[0] === '\\') {
            $fqcnWithoutLeadingSlash = mb_substr($fqcnWithoutLeadingSlash, 1);
        }

        return new AutocompletionSuggestion(
            $fqcnWithoutLeadingSlash,
            SuggestionKind::IMPORT,
            $namespace['name'],
            $fqcnWithoutLeadingSlash,
            null,
            [
                'isDeprecated' => false,
                'returnTypes'  => 'namespace'
            ]
        );
    }
}
