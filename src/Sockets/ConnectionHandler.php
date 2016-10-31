<?php

namespace PhpIntegrator\Sockets;

use UnexpectedValueException;

use React\EventLoop\LoopInterface;

use React\Socket\Connection;

/**
 * Handles socket connections.
 */
class ConnectionHandler
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
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->setup();
    }

    /**
     * @return void
     */
    protected function setup()
    {
        $this->resetRequestState();

        $this->connection->on('data', [$this, 'onDataReceived']);
        $this->connection->on('end', [$this, 'onEnded']);
        $this->connection->on('close', [$this, 'onClosed']);
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
     * @param string $data
     */
    public function onDataReceived($data)
    {
        try {
            $this->processData($data);
        } catch (RequestParsingException $e) {
            $this->handleRequestParsingException($e);
        }
    }

    /**
     * @return void
     */
    public function onEnded()
    {

    }

    /**
     * @return void
     */
    public function onClosed()
    {

    }

    /**
     * @param RequestParsingException $e
     */
    protected function handleRequestParsingException(RequestParsingException $e)
    {
        $this->resetRequestState();
    }

    /**
     * @param string $data
     */
    protected function processData($data)
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

                $this->writeRawResponse($responseContent);

                $this->resetRequestState();
            }
        }

        $data = substr($data, $bytesRead);

        if (strlen($data) > 0) {
            $this->processData($data);
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
     * @param string $content
     */
    protected function writeRawResponse($content)
    {
        $this->connection->write('Content-Length: ' . strlen($content) . self::HEADER_DELIMITER);
        $this->connection->write(self::HEADER_DELIMITER);
        $this->connection->write($content);
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
