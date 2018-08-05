<?php

namespace Serenata\Linting;

use PhpParser\Parser;
use PhpParser\ErrorHandler;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Utility\PositionEncoding;

/**
 * Lints a file syntactically as well as semantically to indicate various problems with its contents.
 */
class Linter
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
     * @param string $code
     *
     * @return array
     */
    public function lint(string $code): array
    {
        // Parse the file to fetch the information we need.
        $nodes = [];
        $parser = $this->parser;

        $handler = new ErrorHandler\Collecting();

        $nodes = $parser->parse($code, $handler);

        $output = [
            'errors'   => [],
            'warnings' => []
        ];

        foreach ($handler->getErrors() as $e) {
            $startLine = $e->getStartLine() >= 0 ? ($e->getStartLine() - 1) : 0;
            $endLine   = $e->getEndLine() >= 0 ? ($e->getEndLine() - 1) : 0;

            $startColumn = $e->hasColumnInfo() ? ($e->getStartColumn($code) - 1) : 0;
            $endColumn   = $e->hasColumnInfo() ? ($e->getEndColumn($code) - 1) : 0;

            $output['errors'][] = [
                'message' =>
                    $e->getMessage(),

                'start' =>
                    (new Position($startLine, $startColumn))->getAsByteOffsetInString($code, PositionEncoding::VALUE),

                'end' =>
                    (new Position($endLine, $endColumn))->getAsByteOffsetInString($code, PositionEncoding::VALUE)
            ];
        }

        if ($nodes === null) {
            return $output;
        }

        return $output;
    }
}
