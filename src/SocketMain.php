<?php

use PhpIntegrator\Sockets\ConnectionHandlerFactory;

use PhpIntegrator\UserInterface\JsonRpcApplication;

require 'Bootstrap.php';

echo "Starting socket server...\n";

$stdinStream = fopen('php://memory', 'w+');

$applicationJsonRpcRequestHandler = new JsonRpcApplication($stdinStream);

$connectionHandlerFactory = new ConnectionHandlerFactory($applicationJsonRpcRequestHandler);

$loop = React\EventLoop\Factory::create();
$socket = new PhpIntegrator\Sockets\SocketServer($loop, 9999, $connectionHandlerFactory);

$loop->run();

fclose($stdinStream);
