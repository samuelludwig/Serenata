<?php

namespace Serenata\Analysis;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

use Serenata\NameQualificationUtilities\ConstantPresenceIndicatorInterface;

/**
 * Checks if a constant exists via Doctrine.
 */
final class DoctrineGlobalConstantExistenceChecker implements ConstantPresenceIndicatorInterface
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
        return !!$this->managerRegistry->getRepository(Structures\Constant::class)->findOneBy([
            'fqcn' => $fullyQualifiedName,
        ]);
    }
}
