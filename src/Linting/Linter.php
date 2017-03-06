<?php

namespace PhpIntegrator\Linting;

use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

/**
 * Lints a file syntactically as well as semantically to indicate various problems with its contents.
 */
class Linter
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var DocblockCorrectnessAnalyzerFactory
     */
    protected $docblockCorrectnessAnalyzerFactory;

    /**
     * @var UnknownClassAnalyzerFactory
     */
    protected $unknownClassAnalyzerFactory;

    /**
     * @var UnknownGlobalConstantAnalyzerFactory
     */
    protected $unknownGlobalConstantAnalyzerFactory;

    /**
     * @var UnknownGlobalFunctionAnalyzerFactory
     */
    protected $unknownGlobalFunctionAnalyzerFactory;

    /**
     * @var UnknownMemberAnalyzerFactory
     */
    protected $unknownMemberAnalyzerFactory;

    /**
     * @var UnusedUseStatementAnalyzerFactory
     */
    protected $unusedUseStatementAnalyzerFactory;

    /**
     * @param Parser                               $parser
     * @param DocblockCorrectnessAnalyzerFactory   $docblockCorrectnessAnalyzerFactory
     * @param UnknownClassAnalyzerFactory          $unknownClassAnalyzerFactory
     * @param UnknownGlobalConstantAnalyzerFactory $unknownGlobalConstantAnalyzerFactory
     * @param UnknownGlobalFunctionAnalyzerFactory $unknownGlobalFunctionAnalyzerFactory
     * @param UnknownMemberAnalyzerFactory         $unknownMemberAnalyzerFactory
     * @param UnusedUseStatementAnalyzerFactory    $unusedUseStatementAnalyzerFactory
     */
    public function __construct(
        Parser $parser,
        DocblockCorrectnessAnalyzerFactory $docblockCorrectnessAnalyzerFactory,
        UnknownClassAnalyzerFactory $unknownClassAnalyzerFactory,
        UnknownGlobalConstantAnalyzerFactory $unknownGlobalConstantAnalyzerFactory,
        UnknownGlobalFunctionAnalyzerFactory $unknownGlobalFunctionAnalyzerFactory,
        UnknownMemberAnalyzerFactory $unknownMemberAnalyzerFactory,
        UnusedUseStatementAnalyzerFactory $unusedUseStatementAnalyzerFactory
    ) {
        $this->parser = $parser;
        $this->docblockCorrectnessAnalyzerFactory = $docblockCorrectnessAnalyzerFactory;
        $this->unknownClassAnalyzerFactory = $unknownClassAnalyzerFactory;
        $this->unknownGlobalConstantAnalyzerFactory = $unknownGlobalConstantAnalyzerFactory;
        $this->unknownGlobalFunctionAnalyzerFactory = $unknownGlobalFunctionAnalyzerFactory;
        $this->unknownMemberAnalyzerFactory = $unknownMemberAnalyzerFactory;
        $this->unusedUseStatementAnalyzerFactory = $unusedUseStatementAnalyzerFactory;
    }

    /**
     * @param string          $file
     * @param string          $code
     * @param LintingSettings $settings
     *
     * @return array
     */
    public function lint(string $file, string $code, LintingSettings $settings): array
    {
        // Parse the file to fetch the information we need.
        $nodes = [];
        $parser = $this->parser;

        $handler = new ErrorHandler\Collecting();

        $nodes = $parser->parse($code, $handler);

        $output = [
            'errors'   => [
                'syntaxErrors' => []
            ],

            'warnings' => []
        ];

        foreach ($handler->getErrors() as $e) {
            $output['errors']['syntaxErrors'][] = [
                'startLine'   => $e->getStartLine() >= 0 ? $e->getStartLine() : null,
                'endLine'     => $e->getEndLine() >= 0 ? $e->getEndLine() : null,
                'startColumn' => $e->hasColumnInfo() ? $e->getStartColumn($code) : null,
                'endColumn'   => $e->hasColumnInfo() ? $e->getEndColumn($code) : null,
                'message'     => $e->getMessage()
            ];
        }

        if ($nodes !== null) {
            $traverser = new NodeTraverser();

            /** @var AnalyzerInterface[] $analyzers */
            $analyzers = [];

            $unknownClassAnalyzer = null;

            if ($settings->getLintUnknownClasses()) {
                $unknownClassAnalyzer = $this->unknownClassAnalyzerFactory->create($file);

                $analyzers[] = $unknownClassAnalyzer;
            }

            $unknownMemberAnalyzer = null;

            if ($settings->getLintUnknownMembers()) {
                $unknownMemberAnalyzer = $this->unknownMemberAnalyzerFactory->create($file, $code);

                $analyzers[] = $unknownMemberAnalyzer;
            }

            $unusedUseStatementAnalyzer = null;

            if ($settings->getLintUnusedUseStatements()) {
                $unusedUseStatementAnalyzer = $this->unusedUseStatementAnalyzerFactory->create();

                $analyzers[] = $unusedUseStatementAnalyzer;
            }

            $docblockCorrectnessAnalyzer = null;

            if ($settings->getLintDocblockCorrectness()) {
                $docblockCorrectnessAnalyzer = $this->docblockCorrectnessAnalyzerFactory->create($code);

                $analyzers[] = $docblockCorrectnessAnalyzer;
            }

            $unknownGlobalConstantAnalyzer = null;

            if ($settings->getLintUnknownGlobalConstants()) {
                $unknownGlobalConstantAnalyzer = $this->unknownGlobalConstantAnalyzerFactory->create();

                $analyzers[] = $unknownGlobalConstantAnalyzer;
            }

            $unknownGlobalFunctionAnalyzer = null;

            if ($settings->getLintUnknownGlobalFunctions()) {
                $unknownGlobalFunctionAnalyzer = $this->unknownGlobalFunctionAnalyzerFactory->create();

                $analyzers[] = $unknownGlobalFunctionAnalyzer;
            }

            foreach ($analyzers as $analyzer) {
                foreach ($analyzer->getVisitors() as $visitor) {
                    $traverser->addVisitor($visitor);
                }
            }

            try {
                $traverser->traverse($nodes);
            } catch (Error $e) {
                $output['errors']['syntaxErrors'][] = [
                    'startLine'   => 0,
                    'endLine'     => 0,
                    'startColumn' => 0,
                    'endColumn'   => 0,
                    'message'     => "Something is semantically wrong. Is there perhaps a duplicate use statement?"
                ];

                return $output;
            }

            if ($unknownClassAnalyzer) {
                $output['errors']['unknownClasses'] = $unknownClassAnalyzer->getOutput();
            }

            if ($unknownMemberAnalyzer) {
                $analyzerOutput = $unknownMemberAnalyzer->getOutput();

                $output['errors']['unknownMembers']   = $analyzerOutput['errors'];
                $output['warnings']['unknownMembers'] = $analyzerOutput['warnings'];
            }

            if ($unknownGlobalFunctionAnalyzer) {
                $output['errors']['unknownGlobalFunctions'] = $unknownGlobalFunctionAnalyzer->getOutput();
            }

            if ($unknownGlobalConstantAnalyzer) {
                $output['errors']['unknownGlobalConstants'] = $unknownGlobalConstantAnalyzer->getOutput();
            }

            if ($docblockCorrectnessAnalyzer) {
                $output['warnings']['docblockIssues'] = $docblockCorrectnessAnalyzer->getOutput();
            }

            if ($unusedUseStatementAnalyzer) {
                $output['warnings']['unusedUseStatements'] = $unusedUseStatementAnalyzer->getOutput();
            }
        }

        return $output;
    }
}
