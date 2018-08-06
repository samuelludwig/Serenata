<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Analysis\NamespaceListProviderInterface;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;

use Serenata\Indexing\Structures\File;

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
     * @var BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param NamespaceListProviderInterface             $namespaceListProvider
     * @param BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param int                                        $resultLimit
     */
    public function __construct(
        NamespaceListProviderInterface $namespaceListProvider,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
        int $resultLimit
    ) {
        $this->namespaceListProvider = $namespaceListProvider;
        $this->bestStringApproximationDeterminer = $bestStringApproximationDeterminer;
        $this->resultLimit = $resultLimit;
    }

    /**
     * @inheritDoc
     */
    public function provide(AutocompletionProviderContext $context): iterable
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
            $context->getPrefix(),
            'name',
            $this->resultLimit
        );

        foreach ($bestApproximations as $namespace) {
            yield $this->createSuggestion($namespace, $context->getPrefix());
        }
    }

    /**
     * @param array $namespace
     * @param string $prefix
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $namespace, string $prefix): AutocompletionSuggestion
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
                'returnTypes'  => 'namespace',
                'prefix'       => $prefix
            ],
            [],
            false
        );
    }
}
