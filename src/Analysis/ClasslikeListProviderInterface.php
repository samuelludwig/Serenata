<?php

namespace Serenata\Analysis;

/**
 * Retrieves a list of classlikes.
 */
interface ClasslikeListProviderInterface
{
    /**
     * @return array<string, array> mapping FQCN's to classlikes.
     */
    public function getAll(): array;
}
