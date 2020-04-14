<?php

namespace A\B;

class Foo {
    public function bar(): string {}
}

/** @var Foo<int,string> $a */
$b = $a->bar();
// <MARKER>
