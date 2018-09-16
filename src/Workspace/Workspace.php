<?php

namespace Serenata\Workspace;

/**
 * Represents a workspace (or "active project").
 */
final class Workspace
{
    /**
     * @var Configuration\WorkspaceConfiguration
     */
    private $configuration;

    /**
     * @param Configuration\WorkspaceConfiguration $configuration
     */
    public function __construct(Configuration\WorkspaceConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return Configuration\WorkspaceConfiguration
     */
    public function getConfiguration(): Configuration\WorkspaceConfiguration
    {
        return $this->configuration;
    }
}
