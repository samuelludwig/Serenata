<?php

namespace PhpIntegrator\Analysis;

/**
 * Defines functionality that must be exposed by classes that provide data to an ClasslikeInfoBuilder.
 */
interface ClasslikeInfoBuilderProviderInterface extends ClasslikeRawConstantDataProviderInterface
{
    /**
     * @param string $fqcn
     *
     * @return array
     */
    public function getClasslikeRawInfo(string $fqcn): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeRawParents(int $id): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeRawChildren(int $id): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeRawInterfaces(int $id): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeRawImplementors(int $id): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeRawTraits(int $id): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeRawTraitUsers(int $id): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeRawProperties(int $id): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeRawMethods(int $id): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeTraitAliasesAssoc(int $id): array;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getClasslikeTraitPrecedencesAssoc(int $id): array;
}
