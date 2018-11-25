<?php

namespace Serenata\UserInterface\Command;

use UnexpectedValueException;

use Serenata\Indexing\Indexer;
use Serenata\Indexing\IndexFilePruner;
use Serenata\Indexing\ManagerRegistry;
use Serenata\Indexing\SchemaInitializer;
use Serenata\Indexing\StorageVersionChecker;

use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

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
     * @param ActiveWorkspaceManager                $activeWorkspaceManager
     * @param WorkspaceConfigurationParserInterface $workspaceConfigurationParser
     * @param ManagerRegistry                       $managerRegistry
     * @param StorageVersionChecker                 $storageVersionChecker
     * @param Indexer                               $indexer
     * @param SchemaInitializer                     $schemaInitializer
     * @param IndexFilePruner                       $indexFilePruner
     */
    public function __construct(
        ActiveWorkspaceManager $activeWorkspaceManager,
        WorkspaceConfigurationParserInterface $workspaceConfigurationParser,
        ManagerRegistry $managerRegistry,
        StorageVersionChecker $storageVersionChecker,
        Indexer $indexer,
        SchemaInitializer $schemaInitializer,
        IndexFilePruner $indexFilePruner
    ) {
        $this->activeWorkspaceManager = $activeWorkspaceManager;
        $this->workspaceConfigurationParser = $workspaceConfigurationParser;
        $this->managerRegistry = $managerRegistry;
        $this->storageVersionChecker = $storageVersionChecker;
        $this->indexer = $indexer;
        $this->schemaInitializer = $schemaInitializer;
        $this->indexFilePruner = $indexFilePruner;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $params = $queueItem->getRequest()->getParams();

        if (!$params) {
            throw new InvalidArgumentsException('Missing parameters for initialize request');
        }

        return $this->initialize(
            $this->createInitializeParamsFromRawArray($params),
            $queueItem->getJsonRpcMessageSender(),
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
     * @param JsonRpcMessageSenderInterface $jsonRpcMessageSender
     * @param JsonRpcRequest                 $jsonRpcRequest
     * @param bool                           $initializeIndexForProject
     *
     * @throws InvalidArgumentsException
     *
     * @return JsonRpcResponse|null
     */
    public function initialize(
        InitializeParams $initializeParams,
        JsonRpcMessageSenderInterface $jsonRpcMessageSender,
        JsonRpcRequest $jsonRpcRequest,
        bool $initializeIndexForProject = true
    ): ?JsonRpcResponse {
        if ($this->activeWorkspaceManager->getActiveWorkspace()) {
            throw new UnexpectedValueException(
                'Initialize was already called, send a shutdown request first if you want to initialize another project'
            );
        }

        $rootUri = $initializeParams->getRootUri();
        $rootPath = $initializeParams->getRootPath();

        if (!$rootUri || !$rootPath) {
            throw new InvalidArgumentsException('Need a rootUri and a rootPath in InitializeParams to function');
        }

        $pathToConfigurationFile = $rootUri . '/.serenata/config.json';

        $workspaceConfiguration = $this->workspaceConfigurationParser->parse($pathToConfigurationFile);

        $this->managerRegistry->setDatabasePath($rootPath . '/.serenata/index.sqlite');

        $this->activeWorkspaceManager->setActiveWorkspace(new Workspace($workspaceConfiguration));

        if (!$this->storageVersionChecker->isUpToDate()) {
            $this->ensureIndexDatabaseDoesNotExist();

            $this->schemaInitializer->initialize();

            if ($initializeIndexForProject) {
                $this->indexer->index(
                    'file://' . __DIR__ . '/../../../vendor/jetbrains/phpstorm-stubs/',
                    false,
                    $jsonRpcMessageSender,
                    null
                );
            }
        } else {
            $this->indexFilePruner->prune();
        }

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

        // This indexing will send the response by itself when it is fully finished. This ensures that the
        // initialization does not complete until the initial index has occurred.
        $this->indexer->index($rootUri, false, $jsonRpcMessageSender, $response);

        return null;
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
