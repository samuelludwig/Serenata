<?php

class A
{
    public string $foo;
}

/** @var A<int,string> $a */
$a = new A();
$a->// <MARKER>;
