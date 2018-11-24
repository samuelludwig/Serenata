<?php

namespace Serenata\UserInterface\Command;

use Serenata\Linting\Linter;
use Serenata\Linting\PublishDiagnosticsParams;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;

use Serenata\Utility\SourceCodeStreamReader;

/**
 * Command that lints a file for various problems.
 *
 * @deprecated Will be removed as soon as all functionality this facilitates is implemented as LSP-compliant requests.
 */
final class LintCommand extends AbstractCommand
{
    /**
     * @var SourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @var Linter
     */
    private $linter;

    /**
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     * @param Linter                 $linter
     */
    public function __construct(SourceCodeStreamReader $sourceCodeStreamReader, Linter $linter)
    {
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
        $this->linter = $linter;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['uri'])) {
            throw new InvalidArgumentsException('"uri" must be supplied');
        }

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['uri']);
        }

        return new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->lint($arguments['uri'], $code)
        );
    }

    /**
     * @param string $uri
     * @param string $code
     *
     * @return PublishDiagnosticsParams
     */
    public function lint(string $uri, string $code): PublishDiagnosticsParams
    {
        return new PublishDiagnosticsParams($uri, $this->linter->lint($code));
    }
}
