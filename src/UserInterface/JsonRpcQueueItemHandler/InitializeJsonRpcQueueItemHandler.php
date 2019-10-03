<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use UnexpectedValueException;

use Serenata\Indexing\Indexer;
use Serenata\Indexing\IndexFilePruner;
use Serenata\Indexing\ManagerRegistry;
use Serenata\Indexing\SchemaInitializer;
use Serenata\Indexing\StorageVersionChecker;

use Serenata\Sockets\JsonRpcQueue;
use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcMessageInterface;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

use Serenata\Utility\MessageType;
use Serenata\Utility\SaveOptions;
use Serenata\Utility\InitializeParams;
use Serenata\Utility\InitializeResult;
use Serenata\Utility\LogMessageParams;
use Serenata\Utility\CompletionOptions;
use Serenata\Utility\ServerCapabilities;
use Serenata\Utility\SignatureHelpOptions;
use Serenata\Utility\TextDocumentSyncOptions;

use Serenata\Workspace\Workspace;
use Serenata\Workspace\ActiveWorkspaceManager;

use Serenata\Workspace\Configuration\Parsing\WorkspaceConfigurationParserInterface;

/**
 * JsonRpcQueueItemHandlerthat initializes a project.
 */
final class InitializeJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
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
     * @var JsonRpcQueue
     */
    private $queue;

    /**
     * @param ActiveWorkspaceManager                $activeWorkspaceManager
     * @param WorkspaceConfigurationParserInterface $workspaceConfigurationParser
     * @param ManagerRegistry                       $managerRegistry
     * @param StorageVersionChecker                 $storageVersionChecker
     * @param Indexer                               $indexer
     * @param SchemaInitializer                     $schemaInitializer
     * @param IndexFilePruner                       $indexFilePruner
     * @param JsonRpcQueue                          $queue
     */
    public function __construct(
        ActiveWorkspaceManager $activeWorkspaceManager,
        WorkspaceConfigurationParserInterface $workspaceConfigurationParser,
        ManagerRegistry $managerRegistry,
        StorageVersionChecker $storageVersionChecker,
        Indexer $indexer,
        SchemaInitializer $schemaInitializer,
        IndexFilePruner $indexFilePruner,
        JsonRpcQueue $queue
    ) {
        $this->activeWorkspaceManager = $activeWorkspaceManager;
        $this->workspaceConfigurationParser = $workspaceConfigurationParser;
        $this->managerRegistry = $managerRegistry;
        $this->storageVersionChecker = $storageVersionChecker;
        $this->indexer = $indexer;
        $this->schemaInitializer = $schemaInitializer;
        $this->indexFilePruner = $indexFilePruner;
        $this->queue = $queue;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcMessageInterface
    {
        $params = $queueItem->getRequest()->getParams();

        if ($params === null || $params === []) {
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
        if ($this->activeWorkspaceManager->getActiveWorkspace() !== null) {
            throw new UnexpectedValueException(
                'Initialize was already called, send a shutdown request first if you want to initialize another project'
            );
        }

        $rootUri = $initializeParams->getRootUri();
        $rootPath = $initializeParams->getRootPath();

        if ($rootUri === null || $rootPath === null) {
            throw new InvalidArgumentsException('Need a rootUri and a rootPath in InitializeParams to function');
        }

        $initializationOptions = $initializeParams->getInitializationOptions();

        $configuration = $initializationOptions['configuration'] ?? null;

        if ($configuration === null) {
            $configuration = $this->getDefaultProjectConfiguration($rootUri);

            $request = new JsonRpcRequest(null, 'serenata/internal/echoMessage', [
                'message' => new JsonRpcRequest(
                    null,
                    'window/logMessage',
                    (new LogMessageParams(
                        MessageType::INFO,
                        'No explicit project configuration found, automatically generating one and using the ' .
                        'system\'s temp folder to store the index database. You should consider setting up a ' .
                        'Serenata configuration file, see also ' .
                        'https://gitlab.com/Serenata/Serenata/wikis/Setting%20Up%20Your%20Project for more ' .
                        'information.'
                    ))->jsonSerialize()
                ),
            ]);

            $this->queue->push(new JsonRpcQueueItem($request, $jsonRpcMessageSender));
        }

        $workspaceConfiguration = $this->workspaceConfigurationParser->parse($configuration);

        $this->managerRegistry->setDatabaseUri($workspaceConfiguration->getIndexDatabaseUri());
        $this->activeWorkspaceManager->setActiveWorkspace(new Workspace($workspaceConfiguration));

        if (!$this->storageVersionChecker->isUpToDate()) {
            $this->resetIndexDatabase();
        }

        $this->indexFilePruner->prune();

        if ($initializeIndexForProject) {
            $urisToIndex = $workspaceConfiguration->getUris();
            $urisToIndex[] = $this->getStubsUri();

            foreach ($urisToIndex as $uri) {
                $this->indexer->index($uri, false, $jsonRpcMessageSender);
            }
        }

        return new JsonRpcResponse(
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
                    true,
                    true,
                    false,
                    false,
                    true,
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
    }

    /**
     * @return string
     */
    private function getStubsUri(): string
    {
        $path = __DIR__ . '/../../../vendor/jetbrains/phpstorm-stubs/';

        if (mb_strpos(__DIR__, '://') === false) {
            // When not in the PHAR, __DIR__ will yield "/path/to/Serenata/src/..." (Linux and macOS) or
            // "C:\path\to\Serenata\src\..." (Windows). The input must be a URI, or indexed items will also not be a
            // URI.
            return 'file://' . $path;
        }

        // When in the PHAR, __DIR__ will yield "phar:///path/to/distribution.phar/absolute/path/to/Serenata/src/...".
        // so there's nothing we need to do here.
        return $path;
    }

    /**
     * @param string $rootUri
     *
     * @return array
     */
    private function getDefaultProjectConfiguration(string $rootUri): array
    {
        $indexDatabaseUri = 'file://' . sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($rootUri);

        $configuration = <<<JSON
{
    "uris": [
        "{$rootUri}"
    ],
    "indexDatabaseUri": "{$indexDatabaseUri}",
    "phpVersion": 7.3,
    "excludedPathExpressions": [],
    "fileExtensions": [
        "php"
    ]
}
JSON;

        return json_decode($configuration, true);
    }

    /**
     * @return void
     */
    private function resetIndexDatabase(): void
    {
        $this->ensureIndexDatabaseDoesNotExist();

        $this->schemaInitializer->initialize();
    }

    /**
     * @return void
     */
    private function ensureIndexDatabaseDoesNotExist(): void
    {
        $this->managerRegistry->ensureConnectionClosed();

        $databaseUri = $this->managerRegistry->getDatabaseUri();

        if ($databaseUri === '') {
            return;
        }

        if (file_exists($databaseUri)) {
            unlink($databaseUri);
        }

        if (file_exists($databaseUri . '-shm')) {
            unlink($databaseUri . '-shm');
        }

        if (file_exists($databaseUri . '-wal')) {
            unlink($databaseUri . '-wal');
        }
    }
}
