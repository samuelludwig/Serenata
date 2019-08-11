<?php

namespace A;

/**
 * @param \Closure $a
 */
function test($a)
{
    test(fn($a) => 5);
}
