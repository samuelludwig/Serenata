<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\Typing\TypeDeducer;

use PhpIntegrator\Parsing\PartialParser;

use PhpIntegrator\Utility\SourceCodeHelpers;
use PhpIntegrator\Utility\SourceCodeStreamReader;

/**
 * Allows deducing the types of an expression (e.g. a call chain, a simple string, ...).
 */
class DeduceTypesCommand extends AbstractCommand
{
    /**
     * @var TypeDeducer
     */
    protected $typeDeducer;

    /**
     * @var PartialParser
     */
    protected $partialParser;

    /**
     * @var SourceCodeStreamReader
     */
    protected $sourceCodeStreamReader;

    /**
     * @param TypeDeducer            $typeDeducer
     * @param PartialParser          $partialParser
     * @param SourceCodeStreamReader $sourceCodeStreamReader
     */
    public function __construct(
        TypeDeducer $typeDeducer,
        PartialParser $partialParser,
        SourceCodeStreamReader $sourceCodeStreamReader
    ) {
        $this->typeDeducer = $typeDeducer;
        $this->partialParser = $partialParser;
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

        $parts = [];

        if (isset($arguments['part'])) {
            $parts = $arguments['part'];
        } else {
            $parts = $this->partialParser->retrieveSanitizedCallStackAt($code, $offset);

            if (!empty($parts) && isset($arguments['ignore-last-element']) && $arguments['ignore-last-element']) {
                array_pop($parts);
            }
        }

        $result = $this->deduceTypes(
           isset($arguments['file']) ? $arguments['file'] : null,
           $code,
           $parts,
           $offset
        );

        return $result;
    }

    /**
     * @param string   $file
     * @param string   $code
     * @param string[] $parts
     * @param int      $offset
     *
     * @return string[]
     */
    protected function deduceTypes($file, $code, array $parts, $offset)
    {
        return $this->typeDeducer->deduceTypes($file, $code, $parts, $offset);
    }
}
