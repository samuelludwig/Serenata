<?php

namespace PhpIntegrator\Analysis\Autocompletion;

/**
 * Autocompletion provider that delegates to another provider and then limits the suggestion count to a fixed amount.
 */
final class LimitingAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var AutocompletionProviderInterface
     */
    private $delegate;

    /**
     * @var int
     */
    private $limit;

    /**
     * @param AutocompletionProviderInterface $delegate
     * @param int                             $limit
     */
    public function __construct(AutocompletionProviderInterface $delegate, int $limit)
    {
        $this->delegate = $delegate;
        $this->limit = $limit;
    }

    /**
     * @inheritDoc
     */
    public function provide(string $code, int $offset): iterable
    {
        $number = 0;

        foreach ($this->delegate->provide($code, $offset) as $suggestion) {
            if ($number++ >= $this->limit) {
                break;
            }

            yield $suggestion;
        }
    }
}
