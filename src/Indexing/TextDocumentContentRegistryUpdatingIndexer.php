<?php

namespace Serenata\Indexing;

use React\Promise\ExtendedPromiseInterface;

use Serenata\Utility\TextDocumentItem;

/**
 * Decorator for {@see FileIndexerInterface} objects that updates a {@see TextDocumentContentRegistry}.
 */
final class TextDocumentContentRegistryUpdatingIndexer implements FileIndexerInterface
{
    /**
     * @var FileIndexerInterface
     */
    private $delegate;

    /**
     * @var TextDocumentContentRegistry
     */
    private $textDocumentContentRegistry;

    /**
     * @param FileIndexerInterface        $delegate
     * @param TextDocumentContentRegistry $textDocumentContentRegistry
     */
    public function __construct(
        FileIndexerInterface $delegate,
        TextDocumentContentRegistry $textDocumentContentRegistry
    ) {
        $this->delegate = $delegate;
        $this->textDocumentContentRegistry = $textDocumentContentRegistry;
    }

    /**
     * @inheritDoc
     */
    public function index(TextDocumentItem $textDocumentItem): ExtendedPromiseInterface
    {
        $promise = $this->delegate->index($textDocumentItem)->then(function ($value) use ($textDocumentItem) {
            $this->textDocumentContentRegistry->update($textDocumentItem->getUri(), $textDocumentItem->getText());

            return $value;
        });

        assert($promise instanceof ExtendedPromiseInterface);

        return $promise;
    }
}
