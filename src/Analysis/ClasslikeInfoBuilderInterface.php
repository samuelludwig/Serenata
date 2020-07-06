<?php

namespace Serenata\Analysis;

/**
 * Interface for classes that build a complete structure of data for a classlike, including children and members.
 */
interface ClasslikeInfoBuilderInterface
{
    /**
     * Retrieves information about the specified structural element.
     *
     * @param string $fqcn
     *
     * @throws ClasslikeBuildingFailedException
     *
     * @return array<string,mixed>
     */
    public function build(string $fqcn): array;
}
