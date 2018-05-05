<?php

namespace Serenata\Analysis;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

/**
 * Checks if a classlike exists via Doctrine.
 */
final class DoctrineClasslikeExistenceChecker implements ClasslikeExistenceCheckerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function doesClassExist(string $fqcn): bool
    {
        return !!$this->managerRegistry->getRepository(Structures\Classlike::class)->findOneBy([
            'fqcn' => $fqcn
        ]);
    }
}
