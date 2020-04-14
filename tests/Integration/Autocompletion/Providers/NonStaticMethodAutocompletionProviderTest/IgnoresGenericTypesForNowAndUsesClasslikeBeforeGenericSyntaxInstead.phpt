<?php

class A
{
    /**
     * @return int|string
     */
    function foo()
    {

    }
}

/** @var A<int,string> $a */
$a = new A();
$a->// <MARKER>;
