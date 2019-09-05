<?php

namespace Serenata\Workspace;

/**
 * Manages the currently active workspace.
 *
 * @final
 */
/*final */class ActiveWorkspaceManager
{
    /**
     * @var Workspace|null
     */
    private $activeWorkspace;

    /**
     * @return Workspace|null
     */
    public function getActiveWorkspace(): ?Workspace
    {
        return $this->activeWorkspace;
    }

    /**
     * @param Workspace|null $activeWorkspace
     */
    public function setActiveWorkspace(?Workspace $activeWorkspace): void
    {
        $this->activeWorkspace = $activeWorkspace;
    }
}
