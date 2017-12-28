<?php

// xdebug will only slow down indexing. Very strangely enough, disabling xdebug doesn't seem to disable this nesting
// level in all cases. See also https://github.com/Gert-dev/php-integrator-base/issues/101 . This appears to be
// confirmed in https://github.com/nikic/PHP-Parser/blob/master/doc/component/Performance.markdown
if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

require __DIR__ . '/Bootstrap.php';

$applicationJsonRpcRequestHandler = new \PhpIntegrator\UserInterface\JsonRpcApplication();

return $applicationJsonRpcRequestHandler->run();
