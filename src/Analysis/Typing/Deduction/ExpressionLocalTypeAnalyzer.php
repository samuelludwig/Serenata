<?php

namespace Serenata\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinterAbstract;

use Serenata\Analysis\Visiting\TypeQueryingVisitor;
use Serenata\Analysis\Visiting\ScopeLimitingVisitor;
use Serenata\Analysis\Visiting\ExpressionTypeInfoMap;

use Serenata\Common\Position;

use Serenata\Parsing\DocblockParser;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Analyzes types affecting expressions (e.g. variables and properties) in a local scope in a file.
 *
 * This class can be used to scan for types that apply to an expression based on local rules, such as conditionals and
 * type overrides.
 */
class ExpressionLocalTypeAnalyzer
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var PrettyPrinterAbstract
     */
    private $prettyPrinter;

    /**
     * @param Parser                $parser
     * @param DocblockParser        $docblockParser
     * @param PrettyPrinterAbstract $prettyPrinter
     */
    public function __construct(Parser $parser, DocblockParser $docblockParser, PrettyPrinterAbstract $prettyPrinter)
    {
        $this->parser = $parser;
        $this->docblockParser = $docblockParser;
        $this->prettyPrinter = $prettyPrinter;
    }

    /**
     * @param \Serenata\Utility\TextDocumentItem $textDocumentItem
     * @param \Serenata\Common\Position          $position
     *
     * @return \Serenata\Analysis\Visiting\ExpressionTypeInfoMap
     */
    public function analyze(TextDocumentItem $textDocumentItem, Position $position): ExpressionTypeInfoMap
    {
        $typeQueryingVisitor = $this->walkTypeQueryingVisitorTo($textDocumentItem, $position);

        return $typeQueryingVisitor->getExpressionTypeInfoMap();
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return TypeQueryingVisitor
     */
    private function walkTypeQueryingVisitorTo(
        TextDocumentItem $textDocumentItem,
        Position $position
    ): TypeQueryingVisitor {
        $nodes = null;

        $handler = new ErrorHandler\Collecting();

        try {
            $nodes = $this->parser->parse($textDocumentItem->getText(), $handler);
        } catch (\PhpParser\Error $e) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        // In php-parser 2.x, this happens when you enter $this-> before an if-statement, because of a syntax error that
        // it can not recover from.
        if ($nodes === null) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        $offset = $position->getAsByteOffsetInString($textDocumentItem->getText(), PositionEncoding::VALUE);

        $scopeLimitingVisitor = new ScopeLimitingVisitor($offset);
        $typeQueryingVisitor = new TypeQueryingVisitor($this->docblockParser, $this->prettyPrinter, $offset);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($scopeLimitingVisitor);
        $traverser->addVisitor($typeQueryingVisitor);
        $traverser->traverse($nodes);

        return $typeQueryingVisitor;
    }
}
