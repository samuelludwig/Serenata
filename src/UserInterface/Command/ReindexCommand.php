<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Indexing\Indexer;

use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;

/**
 * Command that reindexes a file or folder.
 */
class ReindexCommand extends AbstractCommand
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
    public function execute(JsonRpcRequest $request, JsonRpcResponseSenderInterface $jsonRpcResponseSender)
    {
        $arguments = $request->getParams() ?: [];

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
            $jsonRpcResponseSender,
            $request->getId()
        );

        return true;
    }
}
