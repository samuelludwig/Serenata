<?php

namespace A;

use DateTime;
use IteratorIterator;
use DateTimeInterface;

$test = new class extends DateTime implements DateTimeInterface {
    public function foo()
    {
        $test = new IteratorIterator();
    }
};
