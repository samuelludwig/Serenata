<?php

namespace Serenata\UserInterface\Command;

use UnexpectedValueException;

use PhpParser\Node;
use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;

/**
 * Trait making a class aware of a parser.
 */
trait ParserAwareTrait
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param string            $code
     * @param ErrorHandler|null $errorHandler
     *
     * @throws UnexpectedValueException
     *
     * @return Node[]
     */
    private function parse(string $code, ?ErrorHandler $errorHandler = null): array
    {
        try {
            $nodes = $this->parser->parse($code, $errorHandler);
        } catch (Error $e) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        if ($nodes === null) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        return $nodes;
    }
}
