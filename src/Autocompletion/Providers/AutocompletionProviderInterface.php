<?php

namespace Serenata\Autocompletion\Providers;

/**
 * Interface for classes that provide autocompletion suggestions at a specific location in a file.
 */
interface AutocompletionProviderInterface
{
    /**
     * @param AutocompletionProviderContext $context
     *
     * @return iterable iterable<AutocompletionSuggestion>
     */
    public function provide(AutocompletionProviderContext $context): iterable;
}
