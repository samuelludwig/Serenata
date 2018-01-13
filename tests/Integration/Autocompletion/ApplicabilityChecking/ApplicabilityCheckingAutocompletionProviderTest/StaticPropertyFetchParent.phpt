<?php

// <INJECTION>

class B extends A
{
    public function bar()
    {
        parent::$f// <MARKER>;
    }
}
