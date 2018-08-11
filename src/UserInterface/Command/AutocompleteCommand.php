<?php

namespace Serenata\UserInterface\Command;

use Serenata\Autocompletion\AutocompletionPrefixDeterminerInterface;

use Serenata\Autocompletion\Providers\AutocompletionProviderContext;
use Serenata\Autocompletion\Providers\AutocompletionProviderInterface;

use Serenata\Common\Position;

use Serenata\Indexing\StorageInterface;
use Serenata\Indexing\FileIndexerInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

use Serenata\Utility\TextDocumentItem;
use Serenata\Utility\SourceCodeStreamReader;

/**
 * Command that shows autocompletion suggestions at a specific location.
 */
class AutocompleteCommand extends AbstractCommand
{
    /**
     * @var AutocompletionProviderInterface
     */
    private $autocompletionProvider;

    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var FileIndexerInterface
     */
    private $fileIndexer;

    /**
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @param AutocompletionProviderInterface         $autocompletionProvider
     * @param SourceCodeStreamReader                  $sourceCodeStreamReader
     * @param StorageInterface                        $storage
     * @param FileIndexerInterface                    $fileIndexer
     * @param AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
     */
    public function __construct(
        AutocompletionProviderInterface $autocompletionProvider,
        SourceCodeStreamReader $sourceCodeStreamReader,
        StorageInterface $storage,
        FileIndexerInterface $fileIndexer,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
    ) {
        $this->autocompletionProvider = $autocompletionProvider;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->storage = $storage;
        $this->fileIndexer = $fileIndexer;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['uri'])) {
            throw new InvalidArgumentsException('"uri" must be supplied');
        } elseif (!isset($arguments['position'])) {
            throw new InvalidArgumentsException('"position" into the source must be supplied');
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['uri']);
        }

        $position = new Position($arguments['position']['line'], $arguments['position']['character']);

        $result = $this->getAutocompletionSuggestions($arguments['uri'], $code, $position);

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $result);
    }

    /**
     * @param string   $uri
     * @param string   $code
     * @param Position $position
     *
     * @return array
     */
    public function getAutocompletionSuggestions(string $uri, string $code, Position $position): array
    {
        // Not used (yet), but still throws an exception when file is not in index.
        $this->storage->getFileByPath($uri);

        // $this->fileIndexer->index($uri, $code);

        return $this->autocompletionProvider->provide(new AutocompletionProviderContext(
            new TextDocumentItem($uri, $code),
            $position,
            $this->autocompletionPrefixDeterminer->determine($code, $position)
        ));
    }
}
