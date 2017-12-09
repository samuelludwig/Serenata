<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpIntegrator\Indexing\Structures\File;

/**
 * Provides autocompletion suggestions at a specific location in a file by aggregating results from delegates.
 */
final class AggregatingAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var AutocompletionProviderInterface[]
     */
    private $delegates;

    /**
     * @param AutocompletionProviderInterface[] ...$delegates
     */
    public function __construct(AutocompletionProviderInterface ...$delegates)
    {
        $this->delegates = $delegates;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        foreach ($this->delegates as $delegate) {
            yield from $delegate->provide($file, $code, $offset);
        }
    }
}
