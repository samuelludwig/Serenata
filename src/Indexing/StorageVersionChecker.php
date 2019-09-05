<?php

namespace Serenata\Indexing;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * Handles storage version checks.
 */
final class StorageVersionChecker
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
     * @return bool
     */
    public function isUpToDate(): bool
    {
        $versionSetting = null;

        try {
            $versionSetting = $this->managerRegistry->getRepository(Structures\Setting::class)->findOneBy([
                'name' => SchemaInitializer::VERSION_SETTING_NAME,
            ]);
        } catch (TableNotFoundException $e) {
            return false;
        }

        if ($versionSetting === null) {
            return false;
        }

        return $versionSetting->getValue() === SchemaInitializer::SCHEMA_VERSION;
    }
}
