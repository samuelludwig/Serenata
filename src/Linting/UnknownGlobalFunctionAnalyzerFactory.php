<?php

namespace Serenata\Linting;

use Serenata\Analysis\Node\FunctionCallNodeFqsenDeterminer;

use Serenata\Indexing\Structures;

use Serenata\NameQualificationUtilities\FunctionPresenceIndicatorInterface;

/**
 * Factory that produces instances of {@see UnknownGlobalFunctionAnalyzer}.
 */
class UnknownGlobalFunctionAnalyzerFactory
{
    /**
     * @var FunctionPresenceIndicatorInterface
     */
    private $functionPresenceIndicator;

    /**
     * @var FunctionCallNodeFqsenDeterminer
     */
    private $functionCallNodeFqsenDeterminer;

    /**
     * @param FunctionPresenceIndicatorInterface $functionPresenceIndicator
     * @param FunctionCallNodeFqsenDeterminer    $functionCallNodeFqsenDeterminer
     */
    public function __construct(
        FunctionPresenceIndicatorInterface $functionPresenceIndicator,
        FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer
    ) {
        $this->functionPresenceIndicator = $functionPresenceIndicator;
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
    }

    /**
     * @return UnknownGlobalFunctionAnalyzer
     */
    public function create(Structures\File $file, string $code): UnknownGlobalFunctionAnalyzer
    {
        return new UnknownGlobalFunctionAnalyzer(
            $this->functionPresenceIndicator,
            $this->functionCallNodeFqsenDeterminer,
            $file,
            $code
        );
    }
}
