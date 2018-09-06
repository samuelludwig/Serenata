<?php

namespace Serenata\Linting;

use PhpParser\Parser;
use PhpParser\ErrorHandler;

use Serenata\Common\Range;
use Serenata\Common\Position;

/**
 * Lints a file syntactically as well as semantically to indicate various problems with its contents.
 */
final class Linter
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
        $handler = new ErrorHandler\Collecting();

        $this->parser->parse($code, $handler);

        $diagnostics = [];

        foreach ($handler->getErrors() as $e) {
            $diagnostics[] = new Diagnostic(
                new Range(
                    new Position(
                        $e->getStartLine() >= 0 ? ($e->getStartLine() - 1) : 0,
                        $e->hasColumnInfo() ? ($e->getStartColumn($code) - 1) : 0
                    ),
                    new Position(
                        $e->getEndLine() >= 0 ? ($e->getEndLine() - 1) : 0,
                        $e->hasColumnInfo() ? ($e->getEndColumn($code) - 1) : 0
                    )
                ),
                DiagnosticSeverity::ERROR,
                null,
                'Syntax',
                $e->getMessage(),
                null
            );
        }

        return $diagnostics;
    }
}
