<?php

namespace Foo;

/**
 *
 */
class A
{
    /**
     * 
     */
    public function method()
    {
        $anonymousClass = new class {
            
        };
    }
    
    /**
     * @var string
     */
    public $propertyThatReturnsToOriginalClass;
}
