<?php

namespace Serenata\Analysis;

use Serenata\Analysis\Typing\TypeAnalyzer;

use Serenata\Common\FilePosition;

use Serenata\NameQualificationUtilities\NameKind;
use Serenata\NameQualificationUtilities\PositionalNameResolverInterface;

/**
 * Positional name resolver that ignores special names (Such as scalar type names).
 */
final class SpecialNameIgnoringPositionalNameResolver implements PositionalNameResolverInterface
{
    /**
     * @var PositionalNameResolverInterface
     */
    private $delegate;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @param PositionalNameResolverInterface $delegate
     * @param TypeAnalyzer                    $typeAnalyzer
     */
    public function __construct(
        PositionalNameResolverInterface $delegate,
        TypeAnalyzer $typeAnalyzer
    ) {
        $this->delegate = $delegate;
        $this->typeAnalyzer = $typeAnalyzer;
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $name, FilePosition $filePosition, string $kind = NameKind::CLASSLIKE): string
    {
        if ($this->typeAnalyzer->isSpecialType($name)) {
            return $name;
        }

        return $this->delegate->resolve($name, $filePosition, $kind);
    }
}
