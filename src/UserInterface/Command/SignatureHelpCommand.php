<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\SignatureHelp\SignatureHelpRetriever;

use PhpIntegrator\Utility\SourceCodeHelpers;
use PhpIntegrator\Utility\SourceCodeStreamReader;

/**
 * Allows fetching signature help (call tips) for a method or function call.
 */
class SignatureHelpCommand extends AbstractCommand
{
    /**
     * @var SignatureHelpRetriever
     */
    protected $signatureHelpRetriever;

    /**
     * @var SourceCodeStreamReader
     */
    protected $sourceCodeStreamReader;

    /**
     * @param SignatureHelpRetriever $signatureHelpRetriever
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     */
    public function __construct(
        SignatureHelpRetriever $signatureHelpRetriever,
        SourceCodeStreamReader $sourceCodeStreamReader
    ) {
        $this->signatureHelpRetriever = $signatureHelpRetriever;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
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
            $offset = SourceCodeHelpers::getByteOffsetFromCharacterOffset($offset, $code);
        }

        return $this->signatureHelpRetriever->get($file, $code, $offset);
    }
}
