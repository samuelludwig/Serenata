<?php

namespace PhpIntegrator\Analysis\Linting;

use PhpIntegrator\Analysis\ConstFetchNodeFqsenDeterminer;
use PhpIntegrator\Analysis\GlobalConstantExistenceCheckerInterface;

use PhpIntegrator\Analysis\Visiting\GlobalConstantUsageFetchingVisitor;

/**
 * Looks for unknown global constant names.
 */
class UnknownGlobalConstantAnalyzer implements AnalyzerInterface
{
    /**
     * @var GlobalConstantExistenceCheckerInterface
     */
    protected $globalConstantExistenceChecker;

    /**
     * @var GlobalConstantUsageFetchingVisitor
     */
    protected $globalConstantUsageFetchingVisitor;

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
    public function getOutput(): array
    {
        $globalConstants = $this->globalConstantUsageFetchingVisitor->getGlobalConstantList();

        $unknownGlobalConstants = [];

        // TODO: Inject this.
        $determiner = new ConstFetchNodeFqsenDeterminer($this->globalConstantExistenceChecker);

        foreach ($globalConstants as $node) {
            $fqsen = $determiner->determine($node);

            if ($this->globalConstantExistenceChecker->doesGlobalConstantExist($fqsen)) {
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
