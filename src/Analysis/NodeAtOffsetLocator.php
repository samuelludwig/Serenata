<?php

namespace Serenata\Analysis;

use UnexpectedValueException;

use Serenata\Analysis\Visiting\NodeFetchingVisitor;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

/**
 * Locates the node at the specified offset in code.
 */
class NodeAtOffsetLocator implements NodeAtOffsetLocatorInterface
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @inheritDoc
     */
    public function locate(string $code, int $position): NodeAtOffsetLocatorResult
    {
        $nodes = [];

        try {
            $nodes = $this->getNodesFromCode($code);
        } catch (UnexpectedValueException $e) {
            return new NodeAtOffsetLocatorResult(null, null, null);
        }

        $visitor = new NodeFetchingVisitor($position);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return new NodeAtOffsetLocatorResult(
            $visitor->getNode(),
            $visitor->getNearestInterestingNode(),
            $visitor->getComment()
        );
    }

    /**
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return Node[]
     */
    private function getNodesFromCode(string $code): array
    {
        $nodes = $this->parser->parse($code, $this->getErrorHandler());

        if ($nodes === null) {
            throw new UnexpectedValueException('No nodes returned after parsing code');
        }

        return $nodes;
    }

    /**
     * @return ErrorHandler\Collecting
     */
    private function getErrorHandler(): ErrorHandler\Collecting
    {
        return new ErrorHandler\Collecting();
    }
}
