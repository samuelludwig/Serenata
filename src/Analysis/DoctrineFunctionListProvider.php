<?php

namespace Serenata\Analysis;

use RuntimeException;

use Doctrine\DBAL\Exception\DriverException;

use Serenata\Analysis\Conversion\FunctionConverter;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

/**
 * Retrieves a list of (global) functions via Doctrine.
 */
final class DoctrineFunctionListProvider implements FunctionListProviderInterface
{
    /**
     * @var FunctionConverter
     */
    private $functionConverter;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param FunctionConverter $functionConverter
     * @param ManagerRegistry   $managerRegistry
     */
    public function __construct(FunctionConverter $functionConverter, ManagerRegistry $managerRegistry)
    {
        $this->functionConverter = $functionConverter;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        $items = [];
        $result = [];

        try {
            $items = $this->managerRegistry->getRepository(Structures\Function_::class)->findAll();
        } catch (DriverException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        foreach ($items as $function) {
            $result[$function->getFqcn()] = $this->functionConverter->convert($function);
        }

        return $result;
    }
}
