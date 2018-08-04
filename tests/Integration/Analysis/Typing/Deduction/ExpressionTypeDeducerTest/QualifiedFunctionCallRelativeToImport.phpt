<?php

namespace A\B;

/**
 * @return string
 */
function foo()
{
}

namespace B\C;

use A\B;

$test = B\foo();

// <MARKER>
