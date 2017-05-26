<?php

namespace PhpIntegrator\Analysis\Autocompletion;

/**
 * Provides autocompletion suggestions at a specific location in a file by aggregating results from delegates.
 */
class AggregatingAutocompletionProvider implements AutocompletionProviderInterface
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
    public function provide(string $code, int $offset): array
    {
        $suggestions = [];

        foreach ($this->delegates as $delegate) {
            $suggestions = array_merge($suggestions, $delegate->provide($code, $offset));
        }

        return $suggestions;
    }
}
