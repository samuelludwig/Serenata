<?php

namespace Serenata\Linting;

use Serenata\Analysis\Node\FunctionCallNodeFqsenDeterminer;

use Serenata\Analysis\Visiting\GlobalFunctionUsageFetchingVisitor;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\NameQualificationUtilities\FunctionPresenceIndicatorInterface;

use Serenata\Utility\PositionEncoding;

/**
 * Looks for unknown global function names (i.e. used during calls).
 */
final class UnknownGlobalFunctionAnalyzer implements AnalyzerInterface
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
     * @var Structures\File
     */
    private $file;

    /**
     * @var string
     */
    private $code;

    /**
     * @var GlobalFunctionUsageFetchingVisitor
     */
    private $globalFunctionUsageFetchingVisitor;

    /**
     * @param FunctionPresenceIndicatorInterface $functionPresenceIndicator
     * @param FunctionCallNodeFqsenDeterminer    $functionCallNodeFqsenDeterminer
     * @param Structures\File                    $file
     * @param string                             $code
     */
    public function __construct(
        FunctionPresenceIndicatorInterface $functionPresenceIndicator,
        FunctionCallNodeFqsenDeterminer $functionCallNodeFqsenDeterminer,
        Structures\File $file,
        string $code
    ) {
        $this->functionPresenceIndicator = $functionPresenceIndicator;
        $this->functionCallNodeFqsenDeterminer = $functionCallNodeFqsenDeterminer;
        $this->file = $file;
        $this->code = $code;

        $this->globalFunctionUsageFetchingVisitor = new GlobalFunctionUsageFetchingVisitor();
    }

    /**
     * @inheritDoc
     */
    public function getVisitors(): array
    {
        return [
            $this->globalFunctionUsageFetchingVisitor
        ];
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        $globalFunctions = $this->globalFunctionUsageFetchingVisitor->getGlobalFunctionCallList();

        $unknownGlobalFunctions = [];

        foreach ($globalFunctions as $node) {
            $fqsen = $this->functionCallNodeFqsenDeterminer->determine(
                $node,
                $this->file,
                Position::createFromByteOffset(
                    $node->getAttribute('startFilePos'),
                    $this->code,
                    PositionEncoding::VALUE
                )
            );

            if ($this->functionPresenceIndicator->isPresent($fqsen)) {
                continue;
            }

            $unknownGlobalFunctions[] = [
                'message' => "Function is not defined or imported anywhere.",
                'start'   => $node->getAttribute('startFilePos') ? $node->getAttribute('startFilePos')   : null,
                'end'     => $node->getAttribute('endFilePos')   ? $node->getAttribute('endFilePos') + 1 : null
            ];
        }

        return $unknownGlobalFunctions;
    }

    /**
     * @inheritDoc
     */
    public function getWarnings(): array
    {
        return [];
    }
}
