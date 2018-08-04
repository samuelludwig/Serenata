<?php

namespace Serenata\Linting;

use Serenata\Analysis\Node\ConstFetchNodeFqsenDeterminer;

use Serenata\Analysis\Visiting\GlobalConstantUsageFetchingVisitor;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\NameQualificationUtilities\ConstantPresenceIndicatorInterface;

use Serenata\Utility\PositionEncoding;

/**
 * Looks for unknown global constant names.
 */
final class UnknownGlobalConstantAnalyzer implements AnalyzerInterface
{
    /**
     * @var ConstantPresenceIndicatorInterface
     */
    private $constantPresenceIndicator;

    /**
     * @var ConstFetchNodeFqsenDeterminer
     */
    private $constFetchNodeFqsenDeterminer;

    /**
     * @var Structures\File
     */
    private $file;

    /**
     * @var string
     */
    private $code;

    /**
     * @var GlobalConstantUsageFetchingVisitor
     */
    private $globalConstantUsageFetchingVisitor;

    /**
     * @param ConstantPresenceIndicatorInterface $constantPresenceIndicator
     * @param ConstFetchNodeFqsenDeterminer      $constFetchNodeFqsenDeterminer
     * @param Structures\File                    $file
     * @param string                             $code
     */
    public function __construct(
        ConstantPresenceIndicatorInterface $constantPresenceIndicator,
        ConstFetchNodeFqsenDeterminer $constFetchNodeFqsenDeterminer,
        Structures\File $file,
        string $code
    ) {
        $this->constantPresenceIndicator = $constantPresenceIndicator;
        $this->constFetchNodeFqsenDeterminer = $constFetchNodeFqsenDeterminer;
        $this->file = $file;
        $this->code = $code;

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

        foreach ($globalConstants as $node) {
            $fqsen = $this->constFetchNodeFqsenDeterminer->determine(
                $node,
                $this->file,
                Position::createFromByteOffset(
                    $node->getAttribute('startFilePos'),
                    $this->code,
                    PositionEncoding::VALUE
                )
            );

            if ($this->constantPresenceIndicator->isPresent($fqsen)) {
                continue;
            }

            $unknownGlobalConstants[] = [
                'message' => "Constant is not defined or imported anywhere.",
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
