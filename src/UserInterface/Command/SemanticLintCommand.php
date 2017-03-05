<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Linting\Linter;

use PhpIntegrator\Utility\SourceCodeStreamReader;

/**
 * Command that lints a file's semantics (i.e. it does not deal with syntax errors, as this is already handled by the
 * indexer).
 */
class SemanticLintCommand extends AbstractCommand
{
    /**
     * @var SourceCodeStreamReader
     */
    protected $sourceCodeStreamReader;

    /**
     * @var Linter
     */
    protected $linter;

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
    public function execute(ArrayAccess $arguments)
    {
        if (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A file name is required for this command.');
        }

        $code = null;

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['file']);
        }

        $output = $this->linter->lint(
            $arguments['file'],
            $code,
            !(isset($arguments['no-unknown-classes']) && $arguments['no-unknown-classes']),
            !(isset($arguments['no-unknown-members']) && $arguments['no-unknown-members']),
            !(isset($arguments['no-unknown-global-functions']) && $arguments['no-unknown-global-functions']),
            !(isset($arguments['no-unknown-global-constants']) && $arguments['no-unknown-global-constants']),
            !(isset($arguments['no-docblock-correctness']) && $arguments['no-docblock-correctness']),
            !(isset($arguments['no-unused-use-statements']) && $arguments['no-unused-use-statements'])
        );

        return $output;
    }
}
