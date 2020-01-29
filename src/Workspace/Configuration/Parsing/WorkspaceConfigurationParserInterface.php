<?php

namespace Serenata\Workspace\Configuration\Parsing;

use Serenata\Workspace\Configuration\WorkspaceConfiguration;

/**
 * Parses a configuration file into a {@see WorkspaceConfiguration}.
 */
interface WorkspaceConfigurationParserInterface
{
    /**
     * @param array<string,mixed> $configuration
     *
     * @throws WorkspaceConfigurationParsingException
     *
     * @return WorkspaceConfiguration
     */
    public function parse(array $configuration): WorkspaceConfiguration;
}
