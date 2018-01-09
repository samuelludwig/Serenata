<?php

class B extends A
{
    public function bar()
    {
        static::$f// <MARKER>;
    }
}
