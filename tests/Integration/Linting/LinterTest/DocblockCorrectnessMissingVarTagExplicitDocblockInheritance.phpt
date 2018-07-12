<?php

namespace A;

class B
{
    /**
     * @var string
     */
    const CONSTANT = 5;

    /**
     * @var int
     */
    protected $property;
    
    /**
     *
     */
    protected $test;
}

class C
{
    /**
     * @inheritDoc
     */
    const CONSTANT = 3;

    /**
     * @inheritDoc
     */
    protected $property;
    
    /**
     * @inheritDoc
     */
    protected $test;
}
