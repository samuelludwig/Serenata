<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use GetOptionKit\OptionCollection;

use PhpIntegrator\Indexing\Indexer;

/**
 * Command that reindexes a file or folder.
 */
class ReindexCommand extends AbstractCommand
{
    /**
     * @var Indexer
     */
    protected $indexer;

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
    public function attachOptions(OptionCollection $optionCollection)
    {
        $optionCollection->add('source+', 'The file or directory to index. Can be passed multiple times to process multiple items at once.')->isa('string');
        $optionCollection->add('exclude+', 'An absolute path to exclude. Can be passed multiple times.')->isa('string');
        $optionCollection->add('extension+', 'An extension (without leading dot) to index. Can be passed multiple times.')->isa('string');
        $optionCollection->add('stdin?', 'If set, file contents will not be read from disk but the contents from STDIN will be used instead.');
        $optionCollection->add('v|verbose?', 'If set, verbose output will be displayed.');
        $optionCollection->add('s|stream-progress?', 'If set, progress will be streamed. Incompatible with verbose mode.');
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        if (!isset($arguments['source']) || empty($arguments['source'])) {
            throw new InvalidArgumentsException('At least one file or directory to index is required for this command.');
        }

        $paths = $arguments['source'];
        $useStdin = isset($arguments['stdin']);

        if ($useStdin) {
            if (count($paths) > 1) {
                throw new InvalidArgumentsException('Reading from STDIN is only possible when a single path is specified!');
            } elseif (!is_file($paths[0])) {
                throw new InvalidArgumentsException('Reading from STDIN is only possible for a single file!');
            }
        }

        $success = $this->indexer->reindex(
            $paths,
            $useStdin,
            isset($arguments['verbose']),
            isset($arguments['stream-progress']),
            isset($arguments['exclude']) ? $arguments['exclude'] : [],
            isset($arguments['extension']) ? $arguments['extension'] : []
        );

        return $success;
    }
}
