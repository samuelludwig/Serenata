<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Conversion\FunctionConverter;

use PhpIntegrator\Indexing\Structures;
use PhpIntegrator\Indexing\ManagerRegistry;

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

     /// @inherited
     public function getAll(): array
     {
         $result = [];

         $items = $this->managerRegistry->getRepository(Structures\Function_::class)->createQueryBuilder('entity')
             ->select('entity')
             ->andWhere('entity.structure IS NULL')
             ->getQuery()
             ->execute();

         foreach ($items as $function) {
             $result[$function->getFqcn()] = $this->functionConverter->convert($function);
         }

         return $result;
     }
}
