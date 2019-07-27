<?php

namespace Serenata\Workspace\Configuration;

/**
 * Represents workspace configuration settings.
 */
final class WorkspaceConfiguration
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string[]
     */
    private $uris;

    /**
     * @var string
     */
    private $indexDatabaseUri;

    /**
     * @var float
     */
    private $phpVersion;

    /**
     * @var string[]
     */
    private $excludedPathExpressions;

    /**
     * @var string[]
     */
    private $fileExtensions;

    /**
     * @param string   $id
     * @param string[] $uris
     * @param string   $indexDatabaseUri
     * @param float    $phpVersion
     * @param string[] $excludedPathExpressions
     * @param string[] $fileExtensions
     */
    public function __construct(
        string $id,
        array $uris,
        string $indexDatabaseUri,
        float $phpVersion,
        array $excludedPathExpressions,
        array $fileExtensions
    ) {
        $this->id = $id;
        $this->uris = $uris;
        $this->indexDatabaseUri = $indexDatabaseUri;
        $this->phpVersion = $phpVersion;
        $this->excludedPathExpressions = $excludedPathExpressions;
        $this->fileExtensions = $fileExtensions;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getUris(): array
    {
        return $this->uris;
    }

    /**
     * @return string
     */
    public function getIndexDatabaseUri(): string
    {
        return $this->indexDatabaseUri;
    }

    /**
     * @return float
     */
    public function getPhpVersion(): float
    {
        return $this->phpVersion;
    }

    /**
     * @return string[]
     */
    public function getExcludedPathExpressions(): array
    {
        return $this->excludedPathExpressions;
    }

    /**
     * @return string[]
     */
    public function getFileExtensions(): array
    {
        return $this->fileExtensions;
    }
}
