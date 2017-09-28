<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Indexing\Indexer;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

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

        if (!isset($arguments['source']) || empty($arguments['source'])) {
            throw new InvalidArgumentsException('At least one file or directory to index is required for this command.');
        }

        $paths = $arguments['source'];
        $useStdin = $arguments['stdin'] ?? false;

        if ($useStdin) {
            if (count($paths) > 1) {
                throw new InvalidArgumentsException('Reading from STDIN is only possible when a single path is specified!');
            } elseif (!is_file($paths[0])) {
                throw new InvalidArgumentsException('Reading from STDIN is only possible for a single file!');
            }
        }

        $this->indexer->index(
            $paths,
            $arguments['extension'] ?? [],
            $arguments['exclude'] ?? [],
            $useStdin,
            $queueItem->getJsonRpcResponseSender(),
            $queueItem->getRequest()->getId()
        );

        return null; // Don't finish request, the indexer sends the response at a later time.
    }
}
