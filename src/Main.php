<?php

use Composer\XdebugHandler\XdebugHandler;

use Serenata\UserInterface\JsonRpcApplication;

require __DIR__ . '/Bootstrap.php';

// xdebug will only slow down indexing. Very strangely enough, disabling xdebug doesn't seem to disable this nesting
// level in all cases. This appears to be confirmed in
// https://github.com/nikic/PHP-Parser/blob/master/doc/component/Performance.markdown
$xdebug = new XdebugHandler('Serenata');
$xdebug->check();

unset($xdebug);

if (XdebugHandler::getRestartSettings() !== null) {
    echo 'Warning: You have the Xdebug extension loaded and enabled. The server has restarted itself without it to ' .
        'avoid severely degraded performance...' . PHP_EOL;
}

$applicationJsonRpcRequestHandler = new JsonRpcApplication();

return $applicationJsonRpcRequestHandler->run();
