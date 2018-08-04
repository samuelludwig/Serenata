<?php

namespace A\B;

/**
 * @return string
 */
const FOO = 'blah';

namespace B\C;

use A\B;

$test = B\FOO;

// <MARKER>
