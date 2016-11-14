<?php

namespace PhpIntegrator\Sockets;

use React\Socket\Connection;

/**
 * Handles socket connections that send JSON-RPC requests via a simple HTTP-like protocol and dispatches the requests
 * to a handler.
 */
class JsonRpcConnectionHandler implements JsonRpcResponseSenderInterface
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
     * @var JsonRpcRequestHandlerInterface
     */
    protected $jsonRpcRequestHandler;

    /**
     * @param Connection                     $connection
     * @param JsonRpcRequestHandlerInterface $jsonRpcRequestHandler
     */
    public function __construct(Connection $connection, JsonRpcRequestHandlerInterface $jsonRpcRequestHandler)
    {
        $this->connection = $connection;
        $this->jsonRpcRequestHandler = $jsonRpcRequestHandler;

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
                $jsonRpcResponse = $this->getJsonRpcResponseForJsonRpcRequest($jsonRpcRequest);

                $this->send($jsonRpcResponse);

                $this->resetRequestState();
            }
        }

        $data = substr($data, $bytesRead);

        if (strlen($data) > 0) {
            $this->processData($data);
        }
    }

    /**
     * @param JsonRpcRequest $request
     *
     * @return JsonRpcResponse
     */
    protected function getJsonRpcResponseForJsonRpcRequest(JsonRpcRequest $request)
    {
        return $this->jsonRpcRequestHandler->handle($request, $this);
    }

    /**
     * @inheritDoc
     */
    public function send(JsonRpcResponse $response)
    {
        $responseContent = json_encode($response);

        if (empty($responseContent)) {
            trigger_error(
                'Empty JSON body encountered after encoding, JSON reports "' . json_last_error_msg() . '"',
                E_USER_WARNING
            );
        }

        $this->writeRawResponse($responseContent);
    }

    /**
     * @param string $content
     *
     * @return JsonRpcRequest
     */
    protected function getJsonRpcRequestFromRequestContent($content)
    {
        return JsonRpcRequest::createFromJson($this->request['content']);
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

        if (count($parts) !== 2) {
            throw new RequestParsingException('Invalid header format encountered');
        }

        list($headerName, $contentLength) = $parts;

        $contentLength = trim($contentLength);

        if (!$contentLength || !is_numeric($contentLength)) {
            throw new RequestParsingException('Content of the Content-Length header is not a valid number');
        }

        return $contentLength;
    }
}
