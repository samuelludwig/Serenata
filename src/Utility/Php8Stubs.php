<?php

// This is just here because ComposerRequireChecker complains that it cannot find this symbol when run on PHP 7.4
// (it is a symbol introduced in PHP 8). We cannot run ComposerRequireChecker on PHP 8 yet, unfortunately.
if (!defined('T_NAME_QUALIFIED')) {
    define('T_NAME_QUALIFIED', 314);
}
