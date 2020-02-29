<?php

namespace Serenata\Analysis;

use UnexpectedValueException;

use PhpParser\Node;
use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

use Serenata\Analysis\Visiting\ScopeLimitingVisitor;

use Serenata\Common\Position;

use Serenata\Utility\TextDocumentItem;

/**
 * Scans for available variables.
 */
final class VariableScanner
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
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return array<string,array<string,mixed>>
     */
    public function getAvailableVariables(TextDocumentItem $textDocumentItem, Position $position): array
    {
        try {
            $nodes = $this->getNodes($textDocumentItem->getText());
        } catch (Error $e) {
            throw new UnexpectedValueException(
                'Could not parse contents of ' . $textDocumentItem->getUri() . ', it may contain syntax errors',
                0,
                $e
            );
        }

        $queryingVisitor = new VariableScanningVisitor($textDocumentItem, $position);
        $scopeLimitingVisitor = new ScopeLimitingVisitor($textDocumentItem, $position);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($scopeLimitingVisitor);
        $traverser->addVisitor($queryingVisitor);
        $traverser->traverse($nodes);

        $variables = $queryingVisitor->getVariablesSortedByProximity();

        // We don't do any type resolution at the moment, but we maintain this format for backwards compatibility.
        $outputVariables = [];

        foreach ($variables as $variable) {
            $outputVariables[$variable] = [
                'name' => $variable,
                'type' => null,
            ];
        }

        return $outputVariables;
    }

    /**
     * @param string $code
     *
     * @throws Error
     *
     * @return Node[]
     */
    private function getNodes(string $code): array
    {
        $handler = new ErrorHandler\Collecting();

        $nodes = $this->parser->parse($code, $handler);

        if ($nodes === null) {
            throw new Error('Unknown syntax error encountered');
        }

        return $nodes;
    }
}
