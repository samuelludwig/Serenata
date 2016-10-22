<?php

namespace A;

trait TestTrait
{
    protected function someMethod()
    {

    }
}

interface TestInterface
{
    public function someMethod();
}

class TestClass implements TestInterface
{
    use TestTrait;
}
