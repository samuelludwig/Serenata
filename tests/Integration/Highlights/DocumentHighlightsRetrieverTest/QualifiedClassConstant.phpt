<?php

namespace A\B {
    class Foo {
        public const BAR = 5;
    };

    $test = Foo::BAR;
}

namespace {
    use A;
    
    $test2 = A\B\Foo::BAR;
}

namespace C {
    $test3 = \A\B\Foo::BAR;
}
