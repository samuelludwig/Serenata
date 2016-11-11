<?php

namespace PhpIntegrator\Indexing;

use LogicException;

use PhpIntegrator\UserInterface\Command\InvalidArgumentsException;

use PhpIntegrator\Utility\SourceCodeStreamReader;

/**
 * Handles indexation of PHP code.
 */
class Indexer
{
    /**
     * @var ProjectIndexer
     */
    protected $projectIndexer;

    /**
     * @var SourceCodeStreamReader
     */
    protected $sourceCodeStreamReader;

    /**
     * @var callable|null
     */
    protected $progressStreamingCallback;

    /**
     * @param ProjectIndexer         $projectIndexer
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     */
    public function __construct(ProjectIndexer $projectIndexer, SourceCodeStreamReader $sourceCodeStreamReader)
    {
        $this->projectIndexer = $projectIndexer;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
    }

    /**
     * @param string[] $paths
     * @param bool     $useStdin
     * @param bool     $showOutput
     * @param bool     $doStreamProgress
     * @param string[] $excludedPaths
     * @param string[] $extensionsToIndex
     *
     * @return bool
     */
    public function reindex(
        array $paths,
        $useStdin,
        $showOutput,
        $doStreamProgress,
        array $excludedPaths = [],
        array $extensionsToIndex = ['php']
    ) {
        if ($useStdin) {
            if (count($paths) > 1) {
                throw new InvalidArgumentsException('Reading from STDIN is only possible when a single path is specified!');
            } elseif (!is_file($paths[0])) {
                throw new InvalidArgumentsException('Reading from STDIN is only possible for a single file!');
            }
        }

        if ($doStreamProgress && !$this->getProgressStreamingCallback()) {
            throw new LogicException('No progress streaming callback configured whilst streaming was requestd!');
        }

        $success = true;
        $exception = null;

        try {
            $this->projectIndexer
                ->setLoggingStream($showOutput ? STDOUT : null)
                ->setProgressStreamingCallback($doStreamProgress ? $this->getProgressStreamingCallback() : null);

            $sourceOverrideMap = [];

            if ($useStdin) {
                $sourceOverrideMap[$paths[0]] = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
            }

            try {
                $this->projectIndexer->index($paths, $extensionsToIndex, $excludedPaths, $sourceOverrideMap);
            } catch (IndexingFailedException $e) {
                $success = false;
            }
        } catch (\Exception $e) {
            $exception = $e;
        }

        if ($exception) {
            throw $exception;
        }

        return $success;
    }

    /**
     * @return callable|null
     */
    public function getProgressStreamingCallback()
    {
        return $this->progressStreamingCallback;
    }

    /**
     * @param callable|null $progressStreamingCallback
     *
     * @return static
     */
    public function setProgressStreamingCallback(callable $progressStreamingCallback = null)
    {
        $this->progressStreamingCallback = $progressStreamingCallback;
        return $this;
    }
}
