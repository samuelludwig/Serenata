<?php

require 'Bootstrap.php';

echo "Starting socket server...\n";

$loop = React\EventLoop\Factory::create();
$socket = new PhpIntegrator\Sockets\SocketServer($loop, 9999);

$loop->run();
