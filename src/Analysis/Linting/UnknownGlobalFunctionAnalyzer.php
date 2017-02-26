<?php

namespace PhpIntegrator\Analysis\Linting;

use PhpIntegrator\Analysis\FunctionCallNodeFqsenDeterminer;
use PhpIntegrator\Analysis\GlobalFunctionExistenceCheckerInterface;

use PhpIntegrator\Analysis\Visiting\NamespaceAttachingVisitor;
use PhpIntegrator\Analysis\Visiting\GlobalFunctionUsageFetchingVisitor;

/**
 * Looks for unknown global function names (i.e. used during calls).
 */
class UnknownGlobalFunctionAnalyzer implements AnalyzerInterface
{
    /**
     * @var NamespaceAttachingVisitor
     */
    protected $namespaceAttachingVisitor;

    /**
     * @var GlobalFunctionUsageFetchingVisitor
     */
    protected $globalFunctionUsageFetchingVisitor;

    /**
     * @var GlobalFunctionExistenceCheckerInterface
     */
    protected $globalFunctionExistenceChecker;

    /**
     * @param GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker
     */
    public function __construct(GlobalFunctionExistenceCheckerInterface $globalFunctionExistenceChecker)
    {
        $this->globalFunctionExistenceChecker = $globalFunctionExistenceChecker;

        $this->namespaceAttachingVisitor = new NamespaceAttachingVisitor();
        $this->globalFunctionUsageFetchingVisitor = new GlobalFunctionUsageFetchingVisitor();
    }

    /**
     * @inheritDoc
     */
    public function getVisitors(): array
    {
        return [
            $this->namespaceAttachingVisitor,
            $this->globalFunctionUsageFetchingVisitor
        ];
    }

    /**
     * @inheritDoc
     */
    public function getOutput(): array
    {
        $globalFunctions = $this->globalFunctionUsageFetchingVisitor->getGlobalFunctionCallList();

        $unknownGlobalFunctions = [];

        // TODO: Inject this.
        $determiner = new FunctionCallNodeFqsenDeterminer($this->globalFunctionExistenceChecker);

        foreach ($globalFunctions as $node) {
            $fqsen = $determiner->determine($node);

            if ($this->globalFunctionExistenceChecker->doesGlobalFunctionExist($fqsen)) {
                continue;
            }

            $unknownGlobalFunctions[] = [
                'name'  => $fqsen,
                'start' => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos')   : null,
                'end'   => $node->getAttribute('endFilePos')   ? $node->getAttribute('endFilePos') + 1 : null
            ];
        }

        return $unknownGlobalFunctions;
    }
}
