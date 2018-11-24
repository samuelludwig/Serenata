<?php

namespace Serenata\Sockets;

use UnexpectedValueException;

/**
 * A request in JSON-RPC 2.0 format.
 *
 * Value object.
 */
final class JsonRpcRequest implements JsonRpcMessageInterface
{
    /**
     * @var string
     */
    private $jsonrpc;

    /**
     * @var string|int|null
     */
    private $id;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array|null
     */
    private $params;

    /**
     * @param string|int|null $id
     * @param string          $method
     * @param array|null      $params
     * @param string          $jsonrpc The version.
     */
    public function __construct($id, string $method, ?array $params = null, string $jsonrpc = '2.0')
    {
        $this->id = $id;
        $this->method = $method;
        $this->params = $params;
        $this->jsonrpc = $jsonrpc;
    }

    /**
     * @return string
     */
    public function getJsonrpc(): string
    {
        return $this->jsonrpc;
    }

    /**
     * Alias for {@see getJsonrpc}.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->getJsonrpc();
    }

    /**
     * @return string|int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array|null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public static function createFromArray(array $array)
    {
        return new static(
            array_key_exists('id', $array) ? $array['id'] : null,
            $array['method'],
            $array['params'],
            $array['jsonrpc']
        );
    }

    /**
     * @param string $json
     *
     * @throws UnexpectedValueException
     *
     * @return static
     */
    public static function createFromJson(string $json)
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new UnexpectedValueException('The specified JSON did not evaluate to an array');
        }

        return static::createFromArray($data);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $data = [
            'jsonrpc' => $this->getJsonrpc(),
            'method'  => $this->getMethod(),
            'params'  => $this->getParams(),
        ];

        // NOTE: Some client implementations (e.g. atom-languageclient) treat a request with an explicit null value for
        // the ID as an actual request instead of a notification, so omit it completely.
        if ($this->getId() !== null) {
            $data['id'] = $this->getId();
        }

        return $data;
    }
}
