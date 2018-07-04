<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Analysis\NamespaceListProviderInterface;

use Serenata\Autocompletion\SuggestionKind;
use Serenata\Autocompletion\AutocompletionSuggestion;
use Serenata\Autocompletion\AutocompletionPrefixDeterminerInterface;

use Serenata\Indexing\Structures\File;

use Serenata\Autocompletion\ApproximateStringMatching\BestStringApproximationDeterminerInterface;

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
     * @var BestStringApproximationDeterminerInterface
     */
    private $bestStringApproximationDeterminer;

    /**
     * @var int
     */
    private $resultLimit;

    /**
     * @param NamespaceListProviderInterface             $namespaceListProvider
     * @param AutocompletionPrefixDeterminerInterface    $autocompletionPrefixDeterminer
     * @param BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer
     * @param int                                        $resultLimit
     */
    public function __construct(
        NamespaceListProviderInterface $namespaceListProvider,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer,
        BestStringApproximationDeterminerInterface $bestStringApproximationDeterminer,
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

        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        $bestApproximations = $this->bestStringApproximationDeterminer->determine(
            $namespaceArrays,
            $prefix,
            'name',
            $this->resultLimit
        );

        foreach ($bestApproximations as $namespace) {
            yield $this->createSuggestion($namespace, $prefix);
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
                'isDeprecated' => false,
                'returnTypes'  => 'namespace',
                'prefix'       => $prefix
            ]
        );
    }
}
