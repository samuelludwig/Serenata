<?php

namespace A\B {
    const CONSTANT = 2;

    $test = CONSTANT;
}

namespace {
    use A;
    
    $test2 = A\B\CONSTANT;
}

namespace C {
    $test3 = \A\B\CONSTANT;
}
