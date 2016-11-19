<?php

use PhpIntegrator\UserInterface\JsonRpcApplication;

require 'Bootstrap.php';

$applicationJsonRpcRequestHandler = new JsonRpcApplication();

return $applicationJsonRpcRequestHandler->run();
