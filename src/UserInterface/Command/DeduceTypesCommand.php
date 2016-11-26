<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\Typing\TypeDeducer;

use PhpIntegrator\Parsing\PartialParser;

use PhpIntegrator\Utility\SourceCodeHelpers;
use PhpIntegrator\Utility\SourceCodeStreamReader;

use PhpParser\Node;

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




        // TODO: Part support has to go away, the entire expression has to be passed as string. Refactor all locations
        // that pass parts to do this.

        if (isset($arguments['part'])) {
            $code = implode('->', $arguments['part']);
        }

        $node = $this->partialParser->getLastNodeAt($code, $offset);

        if ($node === null) {
            return [];
        }

        if (isset($arguments['ignore-last-element']) && $arguments['ignore-last-element']) {
            $node = $this->getNodeWithoutLastElement($node);
        }

        $result = $this->deduceTypes(
           isset($arguments['file']) ? $arguments['file'] : null,
           $code,
           $node,
           $offset
        );

        return $result;
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    protected function getNodeWithoutLastElement(Node $node)
    {
        if ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\PropertyFetch) {
            return $node->var;
        } elseif ($node instanceof Node\Expr\StaticCall ||
            $node instanceof Node\Expr\StaticPropertyFetch ||
            $node instanceof Node\Expr\ClassConstFetch
        ) {
            return $node->class;
        }

        return $node;
    }

    /**
     * @param string $file
     * @param string $code
     * @param Node   $node
     * @param int    $offset
     *
     * @return string[]
     */
    protected function deduceTypes($file, $code, Node $node, $offset)
    {
        return $this->typeDeducer->deduceTypesFromNode($file, $code, $node, $offset);
    }

    /**
     * @param string $file
     * @param string $code
     * @param string $expression
     * @param int    $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromExpression($file, $code, $expression, $offset)
    {
        $node = $this->partialParser->getLastNodeAt($expression, $offset);

        return $this->deduceTypes($file, $code, $node, $offset);
    }
}
