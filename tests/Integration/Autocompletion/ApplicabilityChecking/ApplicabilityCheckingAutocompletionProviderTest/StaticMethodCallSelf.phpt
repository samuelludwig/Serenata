<?php

// <INJECTION>

class B extends A
{
    public function bar()
    {
        self::f// <MARKER>();
    }
}
