<?php

namespace Serenata\Analysis;

/**
 * Defines functionality that must be exposed by classes that provide raw data about the constants of a classlike.
 */
interface ClasslikeRawConstantDataProviderInterface
{
    /**
     * @param int $id
     *
     * @return array<array<string,mixed>>
     */
    public function getClasslikeRawConstants(int $id): array;
}
