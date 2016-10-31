<?php

namespace PhpIntegrator\Sockets;

/**
 * Handles {@see JsonRpcRequestHandler}s.
 */
class ApplicationJsonRpcRequestHandler implements JsonRpcRequestHandlerInterface
{
    /// @inherited
    public function handle(JsonRpcRequest $request)
    {
        $responseData = $this->getResultFor($request);

        return new JsonRpcResponse(
            $request->getId(),
            json_decode($responseData, true) // TODO: Commands should not encode, should be handled by outer layer.
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
        $output = (new \PhpIntegrator\UserInterface\Application())->handle($arguments, $stdinStream);

        if ($stdinStream) {
            fclose($stdinStream);
        }

        return $output;
    }
}
