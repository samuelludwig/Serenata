<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\ClearableCacheInterface;

use Serenata\Indexing\Indexer;
use Serenata\Indexing\IndexFilePruner;
use Serenata\Indexing\ManagerRegistry;
use Serenata\Indexing\SchemaInitializer;
use Serenata\Indexing\StorageVersionChecker;

use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcResponseSenderInterface;

use Serenata\Utility\SaveOptions;
use Serenata\Utility\InitializeParams;
use Serenata\Utility\InitializeResult;
use Serenata\Utility\CompletionOptions;
use Serenata\Utility\ServerCapabilities;
use Serenata\Utility\SignatureHelpOptions;
use Serenata\Utility\TextDocumentSyncOptions;

use Serenata\Workspace\Workspace;
use Serenata\Workspace\ActiveWorkspaceManager;

use Serenata\Workspace\Configuration\Parsing\WorkspaceConfigurationParserInterface;

/**
 * Command that initializes a project.
 */
final class InitializeCommand extends AbstractCommand
{
    /**
     * @var ActiveWorkspaceManager
     */
    private $activeWorkspaceManager;

    /**
     * @var WorkspaceConfigurationParserInterface
     */
    private $workspaceConfigurationParser;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var StorageVersionChecker
     */
    private $storageVersionChecker;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var SchemaInitializer
     */
    private $schemaInitializer;

    /**
     * @var IndexFilePruner
     */
    private $indexFilePruner;

    /**
     * @var ClearableCacheInterface
     */
    private $cache;

    /**
     * @param ActiveWorkspaceManager                $activeWorkspaceManager
     * @param WorkspaceConfigurationParserInterface $workspaceConfigurationParser
     * @param ManagerRegistry                       $managerRegistry
     * @param StorageVersionChecker                 $storageVersionChecker
     * @param Indexer                               $indexer
     * @param SchemaInitializer                     $schemaInitializer
     * @param IndexFilePruner                       $indexFilePruner
     * @param ClearableCacheInterface               $cache
     */
    public function __construct(
        ActiveWorkspaceManager $activeWorkspaceManager,
        WorkspaceConfigurationParserInterface $workspaceConfigurationParser,
        ManagerRegistry $managerRegistry,
        StorageVersionChecker $storageVersionChecker,
        Indexer $indexer,
        SchemaInitializer $schemaInitializer,
        IndexFilePruner $indexFilePruner,
        ClearableCacheInterface $cache
    ) {
        $this->activeWorkspaceManager = $activeWorkspaceManager;
        $this->workspaceConfigurationParser = $workspaceConfigurationParser;
        $this->managerRegistry = $managerRegistry;
        $this->storageVersionChecker = $storageVersionChecker;
        $this->indexer = $indexer;
        $this->schemaInitializer = $schemaInitializer;
        $this->indexFilePruner = $indexFilePruner;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $params = $queueItem->getRequest()->getParams();

        if (!$params) {
            throw new InvalidArgumentsException('Missing parameters for initialize request');
        }

        return $this->initialize(
            $this->createInitializeParamsFromRawArray($params),
            $queueItem->getJsonRpcResponseSender(),
            $queueItem->getRequest()
        );
    }

    /**
     * @param array $params
     *
     * @return InitializeParams
     */
    private function createInitializeParamsFromRawArray(array $params): InitializeParams
    {
        return new InitializeParams(
            $params['processId'],
            $params['rootPath'] ?? null,
            $params['rootUri'],
            $params['initializationOptions'] ?? null,
            $params['capabilities'],
            $params['trace'] ?? 'off',
            $params['workspaceFolders'] ?? null
        );
    }

    /**
     * @param InitializeParams               $initializeParams
     * @param JsonRpcResponseSenderInterface $jsonRpcResponseSender
     * @param JsonRpcRequest                 $jsonRpcRequest
     * @param bool                           $initializeIndexForProject
     *
     * @throws InvalidArgumentsException
     *
     * @return JsonRpcResponse|null
     */
    public function initialize(
        InitializeParams $initializeParams,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender,
        JsonRpcRequest $jsonRpcRequest,
        bool $initializeIndexForProject = true
    ): ?JsonRpcResponse {
        $rootPath = $initializeParams->getRootPath();

        if (!$rootPath) {
            throw new InvalidArgumentsException('Need a rootPath in InitializeParams to function');
        }

        $pathToConfigurationFile = $rootPath . '/.serenata/config.json';

        $workspaceConfiguration = $this->workspaceConfigurationParser->parse($pathToConfigurationFile);

        $this->setDatabaseFile($rootPath . '/.serenata/index.sqlite');

        if (!$this->storageVersionChecker->isUpToDate()) {
            $this->ensureIndexDatabaseDoesNotExist();

            $this->schemaInitializer->initialize();

            if ($initializeIndexForProject) {
                $this->indexer->index(
                    ['file://' . __DIR__ . '/../../../vendor/jetbrains/phpstorm-stubs/'],
                    $workspaceConfiguration->getFileExtensions(),
                    [],
                    false,
                    $jsonRpcResponseSender,
                    null
                );
            }
        } else {
            $this->indexFilePruner->prune();
        }

        $this->cache->clearCache();

        $this->activeWorkspaceManager->setActiveWorkspace(new Workspace($workspaceConfiguration));

        $response = new JsonRpcResponse(
            $jsonRpcRequest->getId(),
            new InitializeResult(
                new ServerCapabilities(
                    new TextDocumentSyncOptions(
                        false,
                        1,
                        false,
                        false,
                        new SaveOptions(true)
                    ),
                    true,
                    new CompletionOptions(false, null),
                    new SignatureHelpOptions(['(', ',']),
                    true,
                    false,
                    false,
                    false,
                    false,
                    true,
                    false,
                    false,
                    false,
                    false,
                    false,
                    null,
                    false,
                    null,
                    false,
                    false,
                    null,
                    null,
                    null
                )
            )
        );

        if (!$initializeIndexForProject) {
            return $response;
        }

        // This indexing will rend the response by itself when it is fully finished. This ensures that the
        // initialization does not complete until the initial index has occurred.
        $this->indexer->index(
            [$rootPath],
            $workspaceConfiguration->getFileExtensions(),
            $workspaceConfiguration->getExcludedPathExpressions(),
            false,
            $jsonRpcResponseSender,
            $response
        );

        return null;
    }

    /**
     * @param string $databaseFile
     */
    private function setDatabaseFile(string $databaseFile): void
    {
        if (!$this->managerRegistry->hasInitialDatabasePathConfigured() ||
            $this->managerRegistry->getDatabasePath() !== $databaseFile
        ) {
            $this->managerRegistry->setDatabasePath($databaseFile);
        }
    }

    /**
     * @return void
     */
    private function ensureIndexDatabaseDoesNotExist(): void
    {
        $this->managerRegistry->ensureConnectionClosed();

        $databasePath = $this->managerRegistry->getDatabasePath();

        if ($databasePath === '') {
            return;
        }

        if (file_exists($databasePath)) {
            unlink($databasePath);
        }

        if (file_exists($databasePath . '-shm')) {
            unlink($databasePath . '-shm');
        }

        if (file_exists($databasePath . '-wal')) {
            unlink($databasePath . '-wal');
        }
    }
}
