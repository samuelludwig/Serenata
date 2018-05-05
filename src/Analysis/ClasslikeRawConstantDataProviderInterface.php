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
     * @return array
     */
    public function getClasslikeRawConstants(int $id): array;
}
