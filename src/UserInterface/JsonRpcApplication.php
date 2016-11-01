<?php

namespace PhpIntegrator\UserInterface;

use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\RequestParsingException;
use PhpIntegrator\Sockets\JsonRpcRequestHandlerInterface;

/**
 * Application extension that can handle JSON-RPC requests.
 *
 * TODO: Should not extend CliApplication.
 */
class JsonRpcApplication extends CliApplication implements JsonRpcRequestHandlerInterface
{
    /**
     * Handles a JSON-PRC request.
     *
     * @param JsonRpcRequest $request
     *
     * @return JsonRpcResponse
     */
    public function handle(JsonRpcRequest $request)
    {
        $responseData = $this->getResultFor($request);

        return new JsonRpcResponse(
            $request->getId(),
            json_decode($responseData, true) // TODO: Commands should not encode, should be handled by outer layer (CLI).
        );
    }

    /**
     * @param JsonRpcRequest $request
     *
     * @return mixed
     */
    protected function getResultFor(JsonRpcRequest $request)
    {
        $params = $request->getParams();

        $arguments = $params['parameters'];

        if (!is_array($arguments)) {
            throw new RequestParsingException('Malformed request content received (expected an \'arguments\' array)');
        }

        array_unshift($arguments, __FILE__);

        $stdinStream = null;

        if ($params['stdinData']) {
            $stdinStream = fopen('php://memory', 'w+');

            fwrite($stdinStream, $params['stdinData']);
            rewind($stdinStream);
        }

        // TODO: Don't create application and container over and over. This might pose a problem as currently
        // classes don't count on state being maintained.
        // TODO: This should not be a CLI application.
        $output = $this->handleCommandLineArguments(
            $arguments,
            $stdinStream
        );

        if ($stdinStream) {
            fclose($stdinStream);
        }

        return $output;
    }
}
