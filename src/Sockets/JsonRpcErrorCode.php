<?php

namespace PhpIntegrator\Sockets;

/**
 * An enumeration of JSON-RPC 2.0 error codes.
 */
class JsonRpcErrorCode
{
    /**
     * @var int
     */
    const PARSE_ERROR      = -32700;

    /**
     * @var int
     */
    const INVALID_REQUEST  = -32600;

    /**
     * @var int
     */
    const METHOD_NOT_FOUND = -32601;

    /**
     * @var int
     */
    const INVALID_PARAMS   = -32602;

    /**
     * @var int
     */
    const INTERNAL_ERROR   = -32603;

    /**
     * @var int
     */
    const UNKNOWN_ERROR    = -32000;
}
