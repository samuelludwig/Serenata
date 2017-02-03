<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Conversion\ConstantConverter;

use PhpIntegrator\Indexing\IndexDatabase;

/**
 * Retrieves a list of global constants.
 */
class GlobalConstantsProvider
{
    /**
     * @var ConstantConverter
     */
    protected $constantConverter;

    /**
     * @var IndexDatabase
     */
    protected $indexDatabase;

    /**
     * @param ConstantConverter $constantConverter
     * @param IndexDatabase     $indexDatabase
     */
    public function __construct(ConstantConverter $constantConverter, IndexDatabase $indexDatabase)
    {
        $this->constantConverter = $constantConverter;
        $this->indexDatabase = $indexDatabase;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        $constants = [];

        foreach ($this->indexDatabase->getGlobalConstants() as $constant) {
            $constants[$constant['fqcn']] = $this->constantConverter->convert($constant);
        }

        return $constants;
    }
}
