<?php

namespace Serenata\Workspace\Configuration\Parsing;

use Serenata\Workspace\Configuration\WorkspaceConfiguration;

/**
 * Parses a JSON configuration file into a {@see WorkspaceConfiguration}.
 */
final class WorkspaceConfigurationJsonParser implements WorkspaceConfigurationParserInterface
{
    /**
     * @inheritDoc
     */
    public function parse(array $configuration): WorkspaceConfiguration
    {
        $this->validate($configuration);

        return new WorkspaceConfiguration(
            $configuration['uris'],
            $configuration['indexDatabaseUri'],
            $configuration['phpVersion'],
            $configuration['excludedPathExpressions'] ?? [],
            $configuration['fileExtensions'] ?? ['php']
        );
    }

    /**
     * @throws WorkspaceConfigurationParsingException
     */
    private function validate(array $configuration): void
    {
        $this->expectKey($configuration, 'uris');
        $this->expectKey($configuration, 'indexDatabaseUri');
        $this->expectKey($configuration, 'phpVersion');
    }

    /**
     * @param array  $data
     * @param string $key
     */
    private function expectKey(array $data, string $key): void
    {
        if (!array_key_exists($key, $data)) {
            throw new WorkspaceConfigurationParsingException('Missing key "' . $key . '" in workspace configuration');
        }
    }
}
