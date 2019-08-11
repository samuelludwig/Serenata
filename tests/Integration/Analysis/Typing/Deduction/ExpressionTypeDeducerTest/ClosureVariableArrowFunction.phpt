<?php

namespace A;

function test(D $d)
{
    $e = new E();

    $closure = fn (A $a) => foo(/* <MARKER> */);
}
