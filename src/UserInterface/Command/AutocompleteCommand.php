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

use Serenata\Utility\PositionEncoding;
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

        if (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A --file must be supplied!');
        } elseif (!isset($arguments['offset'])) {
            throw new InvalidArgumentsException('An --offset must be supplied into the source code!');
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['file']);
        }

        $offset = $arguments['offset'];

        if (isset($arguments['charoffset']) && $arguments['charoffset'] == true) {
            $offset = $this->getByteOffsetFromCharacterOffset($offset, $code);
        }

        $result = $this->getAutocompletionSuggestions($arguments['file'], $code, $offset);

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $result);
    }

    /**
     * @param string $filePath
     * @param string $code
     * @param int    $offset
     *
     * @return array
     */
    public function getAutocompletionSuggestions(string $filePath, string $code, int $offset): array
    {
        $file = $this->storage->getFileByPath($filePath);

        // $this->fileIndexer->index($filePath, $code);

        $position = Position::createFromByteOffset($offset, $code, PositionEncoding::VALUE);

        return $this->autocompletionProvider->provide(new AutocompletionProviderContext(
            new TextDocumentItem($filePath, $code),
            $position,
            $this->autocompletionPrefixDeterminer->determine($code, $position)
        ));
    }
}
