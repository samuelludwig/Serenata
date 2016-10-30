<?php

require 'Bootstrap.php';

$arguments = $argv;

$response = (new \PhpIntegrator\UserInterface\Application())->handle($arguments);

echo $response;
