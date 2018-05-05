<?php

namespace Serenata\Analysis;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

use Serenata\NameQualificationUtilities\FunctionPresenceIndicatorInterface;

/**
 * Checks if a function exists via Doctrine.
 */
final class DoctrineGlobalFunctionExistenceChecker implements FunctionPresenceIndicatorInterface
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
    public function isPresent(string $fullyQualifiedName): bool
    {
        return !!$this->managerRegistry->getRepository(Structures\Function_::class)->findOneBy([
            'fqcn' => $fullyQualifiedName
        ]);
    }
}
