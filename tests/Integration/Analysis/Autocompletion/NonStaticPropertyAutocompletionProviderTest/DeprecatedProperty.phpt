<?php

class A
{
    /**
     * @deprecated
     */
    public $foo;
}

$a = new A();
$a->// <MARKER>
