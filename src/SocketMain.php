<?php

use PhpIntegrator\Sockets\ConnectionHandlerFactory;

use PhpIntegrator\UserInterface\JsonRpcApplication;

require 'Bootstrap.php';

echo "Starting socket server...\n";

$applicationJsonRpcRequestHandler = new JsonRpcApplication();

$connectionHandlerFactory = new ConnectionHandlerFactory($applicationJsonRpcRequestHandler);

$loop = React\EventLoop\Factory::create();
$socket = new PhpIntegrator\Sockets\SocketServer($loop, 9999, $connectionHandlerFactory);

$loop->run();
