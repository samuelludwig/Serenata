<?php

namespace Serenata\UserInterface\Command;

use Serenata\Indexing\Indexer;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that reindexes a file or folder.
 */
final class ReindexCommand extends AbstractCommand
{
    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @param Indexer $indexer
     */
    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['uris']) || count($arguments['uris']) === 0) {
            throw new InvalidArgumentsException('"uris" must be supplied with at least one file or directory to index');
        }

        $uris = $arguments['uris'];
        $useStdin = $arguments['stdin'] ?? false;

        if ($useStdin) {
            if (count($uris) > 1) {
                throw new InvalidArgumentsException(
                    'Reading from STDIN is only possible when a single path is specified'
                );
            } elseif (!is_file($uris[0])) {
                throw new InvalidArgumentsException('Reading from STDIN is only possible for files');
            }
        }

        $this->indexer->index(
            $uris,
            $arguments['extension'] ?? [],
            $arguments['exclude'] ?? [],
            $useStdin,
            $queueItem->getJsonRpcResponseSender(),
            $queueItem->getRequest()->getId()
        );

        return null; // Don't finish request, the indexer sends the response at a later time.
    }
}
