<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\GlobalConstantExistenceCheckerInterface;

use PhpIntegrator\Analysis\Node\ConstNameNodeFqsenDeterminer;

use PhpIntegrator\Analysis\Visiting\GlobalConstantUsageFetchingVisitor;

/**
 * Looks for unknown global constant names.
 */
class UnknownGlobalConstantAnalyzer implements AnalyzerInterface
{
    /**
     * @var GlobalConstantExistenceCheckerInterface
     */
    private $globalConstantExistenceChecker;

    /**
     * @var GlobalConstantUsageFetchingVisitor
     */
    private $globalConstantUsageFetchingVisitor;

    /**
     * @param GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker
     */
    public function __construct(GlobalConstantExistenceCheckerInterface $globalConstantExistenceChecker)
    {
        $this->globalConstantExistenceChecker = $globalConstantExistenceChecker;

        $this->globalConstantUsageFetchingVisitor = new GlobalConstantUsageFetchingVisitor();
    }

    /**
     * @inheritDoc
     */
    public function getVisitors(): array
    {
        return [
            $this->globalConstantUsageFetchingVisitor
        ];
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        $globalConstants = $this->globalConstantUsageFetchingVisitor->getGlobalConstantList();

        $unknownGlobalConstants = [];

        // TODO: Inject this.
        $determiner = new ConstNameNodeFqsenDeterminer($this->globalConstantExistenceChecker);

        foreach ($globalConstants as $node) {
            $fqsen = $determiner->determine($node->name);

            if ($this->globalConstantExistenceChecker->exists($fqsen)) {
                continue;
            }

            $unknownGlobalConstants[] = [
                'message' => "Constant ‘{$fqsen}’ is not defined or imported anywhere.",
                'start'   => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos')   : null,
                'end'     => $node->getAttribute('endFilePos')   ? $node->getAttribute('endFilePos') + 1 : null
            ];
        }

        return $unknownGlobalConstants;
    }

    /**
     * @inheritDoc
     */
    public function getWarnings(): array
    {
        return [];
    }
}
