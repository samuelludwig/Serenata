<?php

namespace PhpIntegrator\Sockets;

use UnexpectedValueException;

use React\EventLoop\LoopInterface;

use React\Socket\Server;
use React\Socket\Connection;

/**
 * Represents a socket server that handles communication with the core.
 */
class SocketServer extends Server
{
    /**
     * @var string
     */
    const HEADER_DELIMITER = "\r\n";

    /**
     * @var array
     */
    protected $request;

    /**
     * @var int
     */
    protected $port;

    /**
     * @param LoopInterface $loop
     * @param int           $port
     */
    public function __construct(LoopInterface $loop, $port)
    {
        parent::__construct($loop);

        $this->port = $port;

        $this->setup();
    }

    /**
     * @return void
     */
    protected function setup()
    {
        $this->on('connection', [$this, 'onConnectionEstablished']);

        $this->listen($this->port);
    }

    /**
     * @return void
     */
    protected function resetRequestState()
    {
        $this->request = [
            'length'           => null,
            'mimeType'         => null,
            'wasBoundaryFound' => false,
            'bytesRead'        => 0,
            'content'          => ''
        ];
    }

    /**
     * @param Connection $connection
     */
    protected function onConnectionEstablished(Connection $connection)
    {
        $this->resetRequestState();

        $connection->on('data', function ($data) use ($connection) {
            $this->onDataReceived($connection, $data);
        });

        $connection->on('end', function () use ($connection) {
            $this->onConnectionEnded($connection);
        });

        $connection->on('close', function () use ($connection) {
            $this->onConnectionClosed($connection);
        });
    }

    /**
     * @param Connection $connection
     * @param string     $data
     */
    protected function onDataReceived(Connection $connection, $data)
    {
        try {
            $this->processConnectionData($connection, $data);
        } catch (RequestParsingException $e) {
            $this->handleRequestParsingException($e);
        }
    }

    /**
     * @param RequestParsingException $e
     */
    protected function handleRequestParsingException(RequestParsingException $e)
    {
        $this->resetRequestState();
    }

    /**
     * @param Connection $connection
     */
    protected function onConnectionEnded(Connection $connection)
    {

    }

    /**
     * @param Connection $connection
     */
    protected function onConnectionClosed(Connection $connection)
    {

    }

    /**
     * @param Connection $connection
     * @param string     $data
     */
    protected function processConnectionData(Connection $connection, $data)
    {
        // TODO: Extract a RequestHandler class.
        // TODO: There could be multiple simultaneous connections sending different requests.

        $bytesRead = 0;

        if ($this->request['length'] === null) {
            $contentLengthHeader = $this->readRawHeader($data);
            $contentLength = $this->getLengthFromContentLengthHeader($contentLengthHeader);

            $this->request['length'] = $contentLength;

            $bytesRead = strlen($contentLengthHeader) + strlen(self::HEADER_DELIMITER);
        } elseif (!$this->request['wasBoundaryFound']) {
            $header = $this->readRawHeader($data);

            if (empty($header)) {
                $this->request['wasBoundaryFound'] = true;
            }

            $bytesRead = strlen($header) + strlen(self::HEADER_DELIMITER);
        } else {
            $bytesRead = min(strlen($data), $this->request['length'] - $this->request['bytesRead']);

            $this->request['content'] .= substr($data, 0, $bytesRead);
            $this->request['bytesRead'] += $bytesRead;

            if ($this->request['bytesRead'] == $this->request['length']) {
                $jsonRpcRequest = $this->getJsonRpcRequestFromRequestContent($this->request['content']);

                $responseContent = $this->getResponseForJsonRpcRequest($jsonRpcRequest);

                $this->writeRawResponse($connection, $responseContent);

                $this->resetRequestState();
            }
        }

        $data = substr($data, $bytesRead);

        if (strlen($data) > 0) {
            $this->processConnectionData($connection, $data);
        }
    }

    /**
     * @param array $request
     *
     * @return mixed
     */
    protected function getOutputForJsonRpcRequest(array $request)
    {
        $arguments = $request['params']['parameters'];

        if (!is_array($arguments)) {
            throw new RequestParsingException('Malformed request content received (expected an \'arguments\' array)');
        }

        array_unshift($arguments, __FILE__);

        $stdinStream = null;

        if ($request['params']['stdinData']) {
            $stdinStream = fopen('php://memory', 'w+');

            fwrite($stdinStream, $request['params']['stdinData']);
            rewind($stdinStream);
        }

        // TODO: Don't create application and container over and over. This might pose a problem as currently
        // classes don't count on state being maintained.
        $output = (new \PhpIntegrator\UserInterface\Application())->handle($arguments, $stdinStream);

        if ($stdinStream) {
            fclose($stdinStream);
        }

        return $output;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    protected function getJsonRpcResponseForJsonRpcRequest(array $request)
    {
        $responseData = $this->getOutputForJsonRpcRequest($request);

        return [
            'id'     => $request['id'],
            'result' => json_decode($responseData, true), // TODO: Commands should not encode, should be handled by outer layer.
            'error'  => null
        ];
    }

    /**
     * @param array $request
     *
     * @return string
     */
    protected function getResponseForJsonRpcRequest(array $request)
    {
        $response = $this->getJsonRpcResponseForJsonRpcRequest($request);

        return json_encode($response);
    }

    /**
     * @param string $content
     *
     * @return array
     */
    protected function getJsonRpcRequestFromRequestContent($content)
    {
        return json_decode($this->request['content'], true);;
    }

    /**
     * @param string $data
     *
     * @throws RequestParsingException
     *
     * @return string
     */
    protected function readRawHeader($data)
    {
        $end = strpos($data, self::HEADER_DELIMITER);

        if ($end === -1) {
            throw new RequestParsingException('Header delimiter not found');
        }

        return substr($data, 0, $end);
    }

    /**
     * @param Connection $connection
     * @param string     $content
     */
    protected function writeRawResponse(Connection $connection, $content)
    {
        $connection->write('Content-Length: ' . strlen($content) . self::HEADER_DELIMITER);
        $connection->write(self::HEADER_DELIMITER);
        $connection->write($content);
    }

    /**
     * @param string $rawHeader
     *
     * @throws RequestParsingException
     *
     * @return int
     */
    protected function getLengthFromContentLengthHeader($rawHeader)
    {
        $parts = explode(':', $rawHeader, 2);

        list($headerName, $contentLength) = $parts;

        $contentLength = trim($contentLength);

        if (!$contentLength || !is_numeric($contentLength)) {
            throw new RequestParsingException('Content of the Content-Length header is not a valid number');
        }

        return $contentLength;
    }
}
