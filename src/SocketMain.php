<?php

use PhpIntegrator\Sockets\ConnectionHandlerFactory;
use PhpIntegrator\Sockets\ApplicationJsonRpcRequestHandler;

require 'Bootstrap.php';

echo "Starting socket server...\n";

$applicationJsonRpcRequestHandler = new ApplicationJsonRpcRequestHandler();

$connectionHandlerFactory = new ConnectionHandlerFactory($applicationJsonRpcRequestHandler);

$loop = React\EventLoop\Factory::create();
$socket = new PhpIntegrator\Sockets\SocketServer($loop, 9999, $connectionHandlerFactory);

$loop->run();
