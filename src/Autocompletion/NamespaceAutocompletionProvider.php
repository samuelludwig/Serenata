<?php

namespace PhpIntegrator\Autocompletion;

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
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @var ApproximateStringMatching\BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param NamespaceListProviderInterface                                       $namespaceListProvider
     * @param AutocompletionPrefixDeterminerInterface                              $autocompletionPrefixDeterminer
     * @param ApproximateStringMatching\BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param int                                                                  $resultLimit
     */
    public function __construct(
        NamespaceListProviderInterface $namespaceListProvider,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer,
        ApproximateStringMatching\BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        int $resultLimit
    ) {
        $this->namespaceListProvider = $namespaceListProvider;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $existingNames = [];

        $namespaceArrays = array_filter(
            $this->namespaceListProvider->getAll(),
            function (array $namespace) use (&$existingNames): bool {
                if ($namespace['name'] === null) {
                    return false;
                } elseif (isset($existingNames[$namespace['name']])) {
                    return false;
                }

                $existingNames[$namespace['name']] = true;

                return true;
            }
        );

        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $namespaceArrays,
            $this->autocompletionPrefixDeterminer->determine($code, $offset),
            'name',
            $this->resultLimit
        );

        foreach ($bestApproximations as $namespace) {
            yield $this->createSuggestion($namespace);
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
            null,
            $fqcnWithoutLeadingSlash,
            null,
            [
                'isDeprecated' => false,
                'returnTypes'  => 'namespace'
            ]
        );
    }
}
