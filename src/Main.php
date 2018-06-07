<?php

use Composer\XdebugHandler\XdebugHandler;

require __DIR__ . '/Bootstrap.php';

$xdebug = new XdebugHandler('Serenata');
$xdebug->setMainScript(__DIR__ . '/Main.php');
$xdebug->check();

unset($xdebug);

if (XdebugHandler::getRestartSettings()) {
    echo 'Warning: You have the Xdebug extension loaded and enabled. The server has restarted itself without it to ' .
        'avoid severely degraded performance...' . PHP_EOL;
}

$applicationJsonRpcRequestHandler = new \Serenata\UserInterface\JsonRpcApplication();

return $applicationJsonRpcRequestHandler->run();
