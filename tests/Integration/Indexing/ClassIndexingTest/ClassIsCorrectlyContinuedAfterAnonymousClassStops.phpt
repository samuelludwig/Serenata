<?php

namespace N;

class Parent1 {}
class Parent2 {}
interface Interface1 {}
interface Interface2 {}
trait Trait1 {}
trait Trait2 {}

class Test extends Parent1 implements Interface1
{
    use Trait1;

    public function method1()
    {
        $anon = new class extends Parent2 implements Interface2 {
            use Trait2;

            public function anonMethod()
            {

            }
        };
    }

    public function method2()
    {

    }
}
