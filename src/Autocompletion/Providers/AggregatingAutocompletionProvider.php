<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Indexing\Structures\File;

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
    public function provide(AutocompletionProviderContext $context): iterable
    {
        foreach ($this->delegates as $delegate) {
            yield from $delegate->provide($context);
        }
    }
}
