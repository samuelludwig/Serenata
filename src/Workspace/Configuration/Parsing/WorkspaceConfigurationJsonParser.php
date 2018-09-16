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
    public function parse(string $uri): WorkspaceConfiguration
    {
        $contents = @file_get_contents($uri);

        if ($contents === false) {
            throw new WorkspaceConfigurationNotFoundException(
                'Workspace configuration could not be read from URI "' . $uri . '"'
            );
        }

        $configuration = json_decode($contents, true);

        if ($configuration === false) {
            throw new WorkspaceConfigurationInvalidFormatException(
                'Workspace configuration in "' . $uri . ' is not valid JSON'
            );
        }

        $this->validate($configuration);

        return new WorkspaceConfiguration(
            $this->generateId($configuration['uris']),
            $configuration['uris'],
            $configuration['phpVersion'],
            $configuration['excludedPathExpressions'] ?? [],
            $configuration['fileExtensions'] ?? ['php']
        );
    }

    /**
     * @param string[] $uris
     *
     * @return string
     */
    private function generateId(array $uris): string
    {
        return md5(array_reduce($uris, function (string $carry, string $uri): string {
            return $carry . $uri;
        }, ''));
    }

    /**
     * @throws WorkspaceConfigurationParsingException
     */
    private function validate(array $configuration): void
    {
        $this->expectKey($configuration, 'uris');
        $this->expectKey($configuration, 'phpVersion');
    }

    /**
     * @param array  $data
     * @param string $key
     */
    private function expectKey(array $data, string $key)
    {
        if (!array_key_exists($key, $data)) {
            throw new WorkspaceConfigurationParsingException('Missing key "' . $key . '" in workspace configuration');
        }
    }
}
