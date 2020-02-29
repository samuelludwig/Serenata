<?php

namespace Serenata\Analysis\Typing\Deduction;

/**
 * Abstract base class for node type deducers.
 */
abstract class AbstractNodeTypeDeducer implements NodeTypeDeducerInterface
{
    /**
     * @param array<string,mixed> $typeArray
     *
     * @return string|null
     */
    protected function fetchResolvedTypeFromTypeArray(array $typeArray): ?string
    {
        return $typeArray['resolvedType'];
    }

    /**
     * @param array<array<string,mixed>> $typeArrays
     *
     * @return array<string|null>
     */
    protected function fetchResolvedTypesFromTypeArrays(array $typeArrays): array
    {
        return array_map([$this, 'fetchResolvedTypeFromTypeArray'], $typeArrays);
    }
}
