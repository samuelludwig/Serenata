<?php

namespace PhpIntegrator\Analysis;

/**
 * Provides data for {@see ClasslikeInfoBuilder} instances.
 *
 * This class uses another backend to handle the actual provision of the raw data (e.g. from persistent storage).
 * Additional relevant data is then applied (decorated) on top of the data from the other provider (e.g. the index).
 */
class ClasslikeInfoBuilderProvider implements ClasslikeInfoBuilderProviderInterface
{
    /**
     * @var ClasslikeInfoBuilderProviderInterface
     */
    private $backend;

    /**
     * @param ClasslikeInfoBuilderProviderInterface $backend
     */
    public function __construct(ClasslikeInfoBuilderProviderInterface $backend)
    {
        $this->backend = $backend;
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawInfo(string $fqcn): ?array
    {
        return $this->backend->getClasslikeRawInfo($fqcn);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawParents(int $id): array
    {
        return $this->backend->getClasslikeRawParents($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawChildren(int $id): array
    {
        return $this->backend->getClasslikeRawChildren($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawInterfaces(int $id): array
    {
        return $this->backend->getClasslikeRawInterfaces($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawImplementors(int $id): array
    {
        return $this->backend->getClasslikeRawImplementors($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawTraits(int $id): array
    {
        return $this->backend->getClasslikeRawTraits($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawTraitUsers(int $id): array
    {
        return $this->backend->getClasslikeRawTraitUsers($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawProperties(int $id): array
    {
        return $this->backend->getClasslikeRawProperties($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawMethods(int $id): array
    {
        return $this->backend->getClasslikeRawMethods($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeTraitAliasesAssoc(int $id): array
    {
        return $this->backend->getClasslikeTraitAliasesAssoc($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeTraitPrecedencesAssoc(int $id): array
    {
        return $this->backend->getClasslikeTraitPrecedencesAssoc($id);
    }

    /**
     * @inheritDoc
     */
    public function getClasslikeRawConstants(int $id): array
    {
        $constants = $this->backend->getClasslikeRawConstants($id);

        array_unshift($constants, $this->getMagicClassConstantRawData($id));

        return $constants;
    }

    /**
     * @param int $structureId
     *
     * @return array
     */
    protected function getMagicClassConstantRawData(int $structureId): array
    {
        return [
            'id'                => -1,
            'name'              => 'class',
            'fqcn'              => null,
            'file_id'           => null,
            'path'              => null,
            'start_line'        => null,
            'end_line'          => null,
            'default_value'     => 'ignored',
            'is_builtin'        => 1,
            'is_deprecated'     => 0,
            'has_docblock'      => 0,
            'short_description' => 'PHP built-in class constant that evaluates to the FCQN.',
            'long_description'  => null,
            'type_description'  => null,
            'types_serialized'  => serialize([['type' => 'string', 'fqcn' => 'string']]),
            'structure_id'      => $structureId
        ];
    }
}
