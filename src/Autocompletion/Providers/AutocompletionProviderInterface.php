<?php

namespace Serenata\Autocompletion\Providers;

use Serenata\Autocompletion\CompletionItem;

/**
 * Interface for classes that provide autocompletion suggestions at a specific location in a file.
 */
interface AutocompletionProviderInterface
{
    /**
     * @param AutocompletionProviderContext $context
     *
     * @return iterable<CompletionItem>
     */
    public function provide(AutocompletionProviderContext $context): iterable;
}
