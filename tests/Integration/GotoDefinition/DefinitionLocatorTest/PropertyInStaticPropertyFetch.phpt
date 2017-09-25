<?php

namespace A;

class B
{    
    protected static $foo = 5;
    
    function test()
    {
        B::$foo = 3;
    }
}
