<?php

namespace Serenata\Workspace\Configuration\Parsing;

use Serenata\Workspace\Configuration\WorkspaceConfiguration;

/**
 * Parses a configuration file into a {@see WorkspaceConfiguration}.
 */
interface WorkspaceConfigurationParserInterface
{
    /**
     * @param string $uri
     *
     * @throws WorkspaceConfigurationParsingException
     *
     * @return WorkspaceConfiguration
     */
    public function parse(string $uri): WorkspaceConfiguration;
}
