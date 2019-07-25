<?php

namespace A\B {
    function foo() {};

    $test = foo();
}

namespace {
    use A;
    
    $test2 = A\B\foo();
}

namespace C {
    $test3 = \A\B\foo();
}
