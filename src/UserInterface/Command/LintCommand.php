<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\FileIndexerInterface;

use PhpIntegrator\Linting\Linter;
use PhpIntegrator\Linting\LintingSettings;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

use PhpIntegrator\Utility\SourceCodeStreamReader;

/**
 * Command that lints a file for various problems.
 */
final class LintCommand extends AbstractCommand
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var Linter
     */
    private $linter;

    /**
     * @var FileIndexerInterface
     */
    private $fileIndexer;

    /**
     * @param StorageInterface       $storage
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     * @param Linter                 $linter
     * @param FileIndexerInterface   $fileIndexer
     */
    public function __construct(
        StorageInterface $storage,
        SourceCodeStreamReader $sourceCodeStreamReader,
        Linter $linter,
        FileIndexerInterface $fileIndexer
    ) {
        $this->storage = $storage;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->linter = $linter;
        $this->fileIndexer = $fileIndexer;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A file name is required for this command.');
        }

        $code = null;

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['file']);
        }

        $settings = new LintingSettings(
            !isset($arguments['no-unknown-classes']) || !$arguments['no-unknown-classes'],
            !isset($arguments['no-unknown-members']) || !$arguments['no-unknown-members'],
            !isset($arguments['no-unknown-global-functions']) || !$arguments['no-unknown-global-functions'],
            !isset($arguments['no-unknown-global-constants']) || !$arguments['no-unknown-global-constants'],
            !isset($arguments['no-docblock-correctness']) || !$arguments['no-docblock-correctness'],
            !isset($arguments['no-unused-use-statements']) || !$arguments['no-unused-use-statements'],
            !isset($arguments['no-missing-documentation']) || !$arguments['no-missing-documentation']
        );

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->lint($arguments['file'], $code, $settings)
        );
    }

    /**
     * @param string          $filePath
     * @param string          $code
     * @param LintingSettings $settings
     *
     * @return array
     */
    public function lint(string $filePath, string $code, LintingSettings $settings): array
    {
        $file = $this->storage->getFileByPath($filePath);

        // $this->fileIndexer->index($filePath, $code);

        return $this->linter->lint($file, $code, $settings);
    }
}
