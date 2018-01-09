<?php

class B extends A
{
    public function bar()
    {
        parent::$// <MARKER>;
    }
}
