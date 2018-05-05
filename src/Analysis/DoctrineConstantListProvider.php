<?php

namespace Serenata\Analysis;

use RuntimeException;

use Doctrine\DBAL\Exception\DriverException;

use Serenata\Analysis\Conversion\ConstantConverter;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

/**
 * Retrieves a list of (global) constants via Doctrine.
 */
final class DoctrineConstantListProvider implements ConstantListProviderInterface
{
    /**
     * @var ConstantConverter
     */
    private $constantConverter;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ConstantConverter $constantConverter
     * @param ManagerRegistry   $managerRegistry
     */
    public function __construct(ConstantConverter $constantConverter, ManagerRegistry $managerRegistry)
    {
        $this->constantConverter = $constantConverter;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        $items = [];
        $constants = [];

        try {
            $items = $this->managerRegistry->getRepository(Structures\Constant::class)->findAll();
        } catch (DriverException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        foreach ($items as $constant) {
            $constants[$constant->getFqcn()] = $this->constantConverter->convert($constant);
        }

        return $constants;
    }
}
