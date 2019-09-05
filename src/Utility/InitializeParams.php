<?php

namespace Serenata\Utility;

/**
 * Represents initialization parameters.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#initialize
 */
final class InitializeParams
{
    /**
     * @var int|null
     */
    private $processId;

    /**
     * @var string|null
     */
    private $rootPath;

    /**
     * @var string|null
     */
    private $rootUri;

    /**
     * @var array|null
     */
    private $initializationOptions;

    /**
     * @var array
     */
    private $capabilities;

    /**
     * @var string|null
     */
    private $trace;

    /**
     * @var array[]|null
     */
    private $workspaceFolders;

    /**
     * @param int|null      $processId
     * @param string|null   $rootPath
     * @param string|null   $rootUri
     * @param array|null    $initializationOptions
     * @param array         $capabilities
     * @param string|null   $trace
     * @param array[]|null  $workspaceFolders
     */
    public function __construct(
        ?int $processId,
        ?string $rootPath,
        ?string $rootUri,
        ?array $initializationOptions,
        $capabilities,
        ?string $trace,
        ?array $workspaceFolders
    ) {
        $this->processId = $processId;
        $this->rootPath = $rootPath;
        $this->rootUri = $rootUri;
        $this->initializationOptions = $initializationOptions;
        $this->capabilities = $capabilities;
        $this->trace = $trace;
        $this->workspaceFolders = $workspaceFolders;
    }

    /**
     * @return int|null
     */
    public function getProcessId(): ?int
    {
        return $this->processId;
    }

    /**
     * @return string|null
     */
    public function getRootPath(): ?string
    {
        return $this->rootPath;
    }

    /**
     * @return string|null
     */
    public function getRootUri(): ?string
    {
        return $this->rootUri;
    }

    /**
     * @return array|null
     */
    public function getInitializationOptions(): ?array
    {
        return $this->initializationOptions;
    }

    /**
     * @return array
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * @return string|null
     */
    public function getTrace(): ?string
    {
        return $this->trace;
    }

    /**
     * @return array[]|null
     */
    public function getWorkspaceFolders(): ?array
    {
        return $this->workspaceFolders;
    }
}
