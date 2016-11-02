<?php

use PhpIntegrator\Sockets\ConnectionHandlerFactory;

use PhpIntegrator\UserInterface\JsonRpcApplication;

require 'Bootstrap.php';

$options = getopt('p:', [
    'server'
]);

if (!isset($options['server'])) {
    echo (new \PhpIntegrator\UserInterface\CliApplication())->handleCommandLineArguments($argv);
    return;
}

if (!isset($options['p'])) {
    die('A port must be passed in order to run in socket server mode');
}

echo "Starting socket server on port {$options['p']}...\n";

$stdinStream = fopen('php://memory', 'w+');

$applicationJsonRpcRequestHandler = new JsonRpcApplication($stdinStream);

$connectionHandlerFactory = new ConnectionHandlerFactory($applicationJsonRpcRequestHandler);

$loop = React\EventLoop\Factory::create();
$socket = new PhpIntegrator\Sockets\SocketServer($loop, $options['p'], $connectionHandlerFactory);

$loop->run();

fclose($stdinStream);
