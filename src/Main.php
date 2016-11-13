<?php

use React\Socket\ConnectionException;

use PhpIntegrator\Sockets\JsonRpcConnectionHandlerFactory;

use PhpIntegrator\UserInterface\JsonRpcApplication;

require 'Bootstrap.php';

$options = getopt('p:');

if (!isset($options['p'])) {
    die('A port must be passed in order to run in socket server mode');
}

echo "Starting socket server on port {$options['p']}...\n";

$stdinStream = fopen('php://memory', 'w+');

$applicationJsonRpcRequestHandler = new JsonRpcApplication($stdinStream);

$connectionHandlerFactory = new JsonRpcConnectionHandlerFactory($applicationJsonRpcRequestHandler);

$loop = React\EventLoop\Factory::create();
$socket = new PhpIntegrator\Sockets\SocketServer($loop, $connectionHandlerFactory);

try {
    $socket->listen($options['p']);
} catch (ConnectionException $e) {
    fwrite(STDERR, 'Socket already in use!');
    fclose($stdinStream);
    return 2;
}

$loop->run();

fclose($stdinStream);
