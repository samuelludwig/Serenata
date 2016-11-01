<?php

require 'Bootstrap.php';

echo (new \PhpIntegrator\UserInterface\CliApplication())->handleCommandLineArguments($argv);
