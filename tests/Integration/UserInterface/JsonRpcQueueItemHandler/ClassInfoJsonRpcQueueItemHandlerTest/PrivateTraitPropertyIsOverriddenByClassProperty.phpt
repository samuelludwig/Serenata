<?php

namespace A;

trait T
{
    private $prop;
}

class C
{
    use T;

    private $prop;
}
