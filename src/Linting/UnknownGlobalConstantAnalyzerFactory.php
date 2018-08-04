<?php

namespace Serenata\Linting;

use Serenata\Analysis\Node\ConstFetchNodeFqsenDeterminer;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\NameQualificationUtilities\ConstantPresenceIndicatorInterface;

/**
 * Factory that produces instances of {@see UnknownGlobalConstantAnalyzer}.
 */
class UnknownGlobalConstantAnalyzerFactory
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
     * @param ConstantPresenceIndicatorInterface $constantPresenceIndicator
     * @param ConstFetchNodeFqsenDeterminer      $constFetchNodeFqsenDeterminer
     */
    public function __construct(
        ConstantPresenceIndicatorInterface $constantPresenceIndicator,
        ConstFetchNodeFqsenDeterminer $constFetchNodeFqsenDeterminer
    ) {
        $this->constantPresenceIndicator = $constantPresenceIndicator;
        $this->constFetchNodeFqsenDeterminer = $constFetchNodeFqsenDeterminer;
    }

    /**
     * @param Structures\File $file
     * @param string          $code
     *
     * @return UnknownGlobalConstantAnalyzer
     */
    public function create(Structures\File $file, string $code): UnknownGlobalConstantAnalyzer
    {
        return new UnknownGlobalConstantAnalyzer(
            $this->constantPresenceIndicator,
            $this->constFetchNodeFqsenDeterminer,
            $file,
            $code
        );
    }
}
