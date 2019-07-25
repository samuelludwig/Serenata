<?php

namespace A\B {
    class Foo {};

    $test = new Foo();
}

namespace {
    use A;
    
    $test2 = new A\B\Foo();
}

namespace C {
    $test3 = new \A\B\Foo();
}
