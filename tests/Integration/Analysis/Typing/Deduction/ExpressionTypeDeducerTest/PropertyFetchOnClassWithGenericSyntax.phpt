<?php

namespace A\B;

class Foo {
    public string $bar;
}

/** @var Foo<int,string> $a */
$b = $a->bar;
// <MARKER>
