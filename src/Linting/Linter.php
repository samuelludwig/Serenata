<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\DocblockAnalyzer;
use PhpIntegrator\Analysis\ClasslikeInfoBuilder;
use PhpIntegrator\Analysis\ClasslikeExistenceChecker;
use PhpIntegrator\Analysis\GlobalConstantExistenceChecker;
use PhpIntegrator\Analysis\GlobalFunctionExistenceChecker;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Parsing\DocblockParser;

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
     * @var FileTypeResolverFactoryInterface
     */
    protected $fileTypeResolverFactory;

    /**
     * @var NodeTypeDeducerInterface
     */
    protected $nodeTypeDeducer;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @var DocblockParser
     */
    protected $docblockParser;

    /**
     * @var TypeAnalyzer
     */
    protected $typeAnalyzer;

    /**
     * @var DocblockAnalyzer
     */
    protected $docblockAnalyzer;

    /**
     * @var ClasslikeExistenceChecker
     */
    protected $classlikeExistenceChecker;

    /**
     * @var GlobalConstantExistenceChecker
     */
    protected $globalConstantExistenceChecker;

    /**
     * @var GlobalFunctionExistenceChecker
     */
    protected $globalFunctionExistenceChecker;

    /**
     * @param Parser                           $parser
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param NodeTypeDeducerInterface         $nodeTypeDeducer
     * @param ClasslikeInfoBuilder             $classlikeInfoBuilder
     * @param DocblockParser                   $docblockParser
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param DocblockAnalyzer                 $docblockAnalyzer
     * @param ClasslikeExistenceChecker        $classlikeExistenceChecker
     * @param GlobalConstantExistenceChecker   $globalConstantExistenceChecker
     * @param GlobalFunctionExistenceChecker   $globalFunctionExistenceChecker
     */
    public function __construct(
        Parser $parser,
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        DocblockAnalyzer $docblockAnalyzer,
        ClasslikeExistenceChecker $classlikeExistenceChecker,
        GlobalConstantExistenceChecker $globalConstantExistenceChecker,
        GlobalFunctionExistenceChecker $globalFunctionExistenceChecker
    ) {
        $this->parser = $parser;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->docblockParser = $docblockParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockAnalyzer = $docblockAnalyzer;
        $this->classlikeExistenceChecker = $classlikeExistenceChecker;
        $this->globalConstantExistenceChecker = $globalConstantExistenceChecker;
        $this->globalFunctionExistenceChecker = $globalFunctionExistenceChecker;
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
            $traverser = new NodeTraverser(false);

            $unknownClassAnalyzer = null;

            if ($settings->getLintUnknownClasses()) {
                $fileTypeResolver = $this->fileTypeResolverFactory->create($file);

                $unknownClassAnalyzer = new UnknownClassAnalyzer(
                    $this->classlikeExistenceChecker,
                    $fileTypeResolver,
                    $this->typeAnalyzer,
                    $this->docblockParser
                );

                foreach ($unknownClassAnalyzer->getVisitors() as $visitor) {
                    $traverser->addVisitor($visitor);
                }
            }

            $unknownMemberAnalyzer = null;

            if ($settings->getLintUnknownMembers()) {
                $unknownMemberAnalyzer = new UnknownMemberAnalyzer(
                    $this->nodeTypeDeducer,
                    $this->classlikeInfoBuilder,
                    $this->typeAnalyzer,
                    $file,
                    $code
                );

                foreach ($unknownMemberAnalyzer->getVisitors() as $visitor) {
                    $traverser->addVisitor($visitor);
                }
            }

            $unusedUseStatementAnalyzer = null;

            if ($settings->getLintUnusedUseStatements()) {
                $unusedUseStatementAnalyzer = new UnusedUseStatementAnalyzer(
                    $this->typeAnalyzer,
                    $this->docblockParser
                );

                foreach ($unusedUseStatementAnalyzer->getVisitors() as $visitor) {
                    $traverser->addVisitor($visitor);
                }
            }

            $docblockCorrectnessAnalyzer = null;

            if ($settings->getLintDocblockCorrectness()) {
                $docblockCorrectnessAnalyzer = new DocblockCorrectnessAnalyzer(
                    $code,
                    $this->classlikeInfoBuilder,
                    $this->docblockParser,
                    $this->typeAnalyzer,
                    $this->docblockAnalyzer
                );

                foreach ($docblockCorrectnessAnalyzer->getVisitors() as $visitor) {
                    $traverser->addVisitor($visitor);
                }
            }

            $unknownGlobalConstantAnalyzer = null;

            if ($settings->getLintUnknownGlobalConstants()) {
                $unknownGlobalConstantAnalyzer = new UnknownGlobalConstantAnalyzer(
                    $this->globalConstantExistenceChecker
                );

                foreach ($unknownGlobalConstantAnalyzer->getVisitors() as $visitor) {
                    $traverser->addVisitor($visitor);
                }
            }

            $unknownGlobalFunctionAnalyzer = null;

            if ($settings->getLintUnknownGlobalFunctions()) {
                $unknownGlobalFunctionAnalyzer = new UnknownGlobalFunctionAnalyzer(
                    $this->globalFunctionExistenceChecker
                );

                foreach ($unknownGlobalFunctionAnalyzer->getVisitors() as $visitor) {
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
