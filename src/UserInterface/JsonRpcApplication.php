k<?php

namespace PhpIntegrator\UserInterface;

use ArrayObject;

use PhpIntegrator\Indexing\IncorrectDatabaseVersionException;

use PhpIntegrator\Sockets\JsonRpcError;
use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcErrorCode;
use PhpIntegrator\Sockets\RequestParsingException;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;
use PhpIntegrator\Sockets\JsonRpcRequestHandlerInterface;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Application extension that can handle JSON-RPC requests.
 */
class JsonRpcApplication extends AbstractApplication implements JsonRpcRequestHandlerInterface
{
    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var string
     */
    protected $databaseFile;

    /**
     * A stream that is used to read and write STDIN data from.
     *
     * As there is no actual STDIN when working with sockets, this temporary stream is used to transparently replace
     * it with another stream.
     *
     * @var resource|null
     */
    protected $stdinStream;

    /**
     * @param resource|null $stdinStream
     */
    public function __construct($stdinStream = null)
    {
        $this->stdinStream = $stdinStream;
    }

    /**
     * @inheritDoc
     */
    public function handle(JsonRpcRequest $request, JsonRpcResponseSenderInterface $jsonRpcResponseSender = null)
    {
        $error = null;
        $result = null;

        try {
            $result = $this->handleRequest($request, $jsonRpcResponseSender);
        } catch (RequestParsingException $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $e->getMessage());
        } catch (Command\InvalidArgumentsException $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::INVALID_PARAMS, $e->getMessage());
        } catch (IncorrectDatabaseVersionException $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::DATABASE_VERSION_MISMATCH, $e->getMessage());
        } catch (\RuntimeException $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::GENERIC_RUNTIME_ERROR, $e->getMessage());
        } catch (\Exception $e) {
            $error = new JsonRpcError(JsonRpcErrorCode::FATAL_SERVER_ERROR, $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        } catch (\Throwable $e) {
            // On PHP < 7, throwable simply won't exist and this clause is never triggered.
            $error = new JsonRpcError(JsonRpcErrorCode::FATAL_SERVER_ERROR, $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        }

        return new JsonRpcResponse($request->getId(), $result, $error);
    }

    /**
     * @param JsonRpcRequest                      $request
     * @param JsonRpcResponseSenderInterface|null $jsonRpcResponseSender
     *
     * @return string
     */
    protected function handleRequest(
        JsonRpcRequest $request,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender = null
    ) {
        $params = $request->getParams();

        $this->configureProgressStreamingCallback($request, $jsonRpcResponseSender);

        if (isset($params['stdinData'])) {
            ftruncate($this->stdinStream, 0);
            fwrite($this->stdinStream, $params['stdinData']);
            rewind($this->stdinStream);
        }

        if (!isset($params['projectName'])) {
            throw new RequestParsingException('Malformed request content received (expected a \'projectName\' field)');
        }

        $this->projectName = $params['projectName'];

        if (isset($params['database'])) {
            $this->setDatabaseFile($params['database']);
        }

        unset(
            $params['stdinData'],
            $params['projectName'],
            $params['database']
        );

        $command = $this->getCommandByMethod($request->getMethod());

        $result = $command->execute(new ArrayObject($params));

        return $result;
    }

    /**
     * @param string $method
     *
     * @return Command\CommandInterface
     */
    protected function getCommandByMethod($method)
    {
        try {
            return $this->getContainer()->get($method . 'Command');
        } catch (ServiceNotFoundException $e) {
            throw new RequestParsingException('Method "' . $method . '" was not found');
        }

        return null; // Never reached.
    }

    /**
     * @param JsonRpcRequest                      $request
     * @param JsonRpcResponseSenderInterface|null $jsonRpcResponseSender
     */
    protected function configureProgressStreamingCallback(
        JsonRpcRequest $request,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender = null
    ) {
        $progressStreamingCallback = null;

        if ($jsonRpcResponseSender) {
            $progressStreamingCallback = $this->createProgressStreamingCallback($request, $jsonRpcResponseSender);
        }

        $this->getContainer()->get('indexer')->setProgressStreamingCallback($progressStreamingCallback);
    }

    /**
     * @inheritDoc
     */
    public function getStdinStream()
    {
        return $this->stdinStream;
    }

    /**
     * @param JsonRpcRequest                      $request
     * @param JsonRpcResponseSenderInterface|null $jsonRpcResponseSender
     */
    public function createProgressStreamingCallback(
        JsonRpcRequest $request,
        JsonRpcResponseSenderInterface $jsonRpcResponseSender = null
    ) {
        return function ($progress) use ($request, $jsonRpcResponseSender) {
            $jsonRpcResponse = new JsonRpcResponse(null, [
                'type'      => 'reindexProgressInformation',
                'requestId' => $request->getId(),
                'progress'  => $progress
            ]);

            $jsonRpcResponseSender->send($jsonRpcResponse);
        };
    }

    /**
     * @inheritDoc
     */
    public function getDatabaseFile()
    {
        return $this->databaseFile;
    }

    /**
     * @inheritDoc
     */
    public function getProjectName()
    {
        return $this->projectName;
    }

    /**
     * @param string $database
     *
     * @return static
     */
    public function setDatabaseFile($databaseFile)
    {
        if ($this->databaseFile !== $databaseFile) {
            $this->getContainer()->get('indexDatabase')->ensureConnectionClosed();
        }

        $this->databaseFile = $databaseFile;
    }
}
