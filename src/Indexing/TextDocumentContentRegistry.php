<?php

namespace Serenata\Indexing;

use Serenata\Utility\SourceCodeStreamReader;

/**
 * Keeps a registry of text documents and their contents.
 *
 * Keeps a registry of text documents along with their textual contents for the purposes of accessing the latest state
 * of documents. It is often necessary to get the latest textual content (or source) of a document for the purposes of
 * analyzing it for yet other purposes such as static analysis.
 *
 * Fetching contents from disk based on the URI is not only not always possible (i.e. in remote scenarios) but is also
 * incorrect as it doesn't necessarily reflect the latest content of a document inside an editor.
 */
final class TextDocumentContentRegistry
{
    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var array
     */
    private $textDocumentContentsMap = [];

    /**
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     */
    public function __construct(SourceCodeStreamReader $sourceCodeStreamReader)
    {
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    public function get(string $uri): string
    {
        if (!isset($this->textDocumentContentsMap[$uri])) {
            $this->textDocumentContentsMap[$uri] = $this->sourceCodeStreamReader->getSourceCodeFromFile($uri);
        }

        return $this->textDocumentContentsMap[$uri];
    }

    /**
     * @param string $uri
     * @param string $contents
     */
    public function update(string $uri, string $contents): void
    {
        $this->textDocumentContentsMap[$uri] = $contents;
    }

    /**
     * @param string $uri
     */
    public function clear(string $uri): void
    {
        unset($this->textDocumentContentsMap[$uri]);
    }
}
