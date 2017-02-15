<?php

namespace PhpIntegrator\Analysis\Linting;

use PhpIntegrator\Analysis\ConstFetchNodeFqsenDeterminer;
use PhpIntegrator\Analysis\GlobalConstantExistanceCheckerInterface;

use PhpIntegrator\Analysis\Visiting\GlobalConstantUsageFetchingVisitor;

/**
 * Looks for unknown global constant names.
 */
class UnknownGlobalConstantAnalyzer implements AnalyzerInterface
{
    /**
     * @var GlobalConstantExistanceCheckerInterface
     */
    protected $globalConstantExistanceChecker;

    /**
     * @var GlobalConstantUsageFetchingVisitor
     */
    protected $globalConstantUsageFetchingVisitor;

    /**
     * @param GlobalConstantExistanceCheckerInterface $globalConstantExistanceChecker
     */
    public function __construct(GlobalConstantExistanceCheckerInterface $globalConstantExistanceChecker)
    {
        $this->globalConstantExistanceChecker = $globalConstantExistanceChecker;

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
    public function getOutput(): array
    {
        $globalConstants = $this->globalConstantUsageFetchingVisitor->getGlobalConstantList();

        $unknownGlobalConstants = [];

        // TODO: Inject this.
        $determiner = new ConstFetchNodeFqsenDeterminer($this->globalConstantExistanceChecker);

        foreach ($globalConstants as $node) {
            $fqsen = $determiner->determine($node);

            if ($this->globalConstantExistanceChecker->doesGlobalConstantExist($fqsen)) {
                continue;
            }

            $unknownGlobalConstants[] = [
                'name'  => $fqsen,
                'start' => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos')   : null,
                'end'   => $node->getAttribute('endFilePos')   ? $node->getAttribute('endFilePos') + 1 : null
            ];;
        }

        return $unknownGlobalConstants;
    }
}
