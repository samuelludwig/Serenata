<?php

namespace A;

trait FirstTrait
{
    protected $firstTraitProperty;

    protected function testAmbiguous()
    {

    }

    protected function testAmbiguousAsWell()
    {

    }

    protected function test()
    {

    }

    protected function overriddenInChild()
    {

    }
}

trait SecondTrait
{
    protected $secondTraitProperty;

    protected function testAmbiguous()
    {

    }

    protected function testAmbiguousAsWell()
    {

    }
}

trait TestTrait
{
    use FirstTrait, SecondTrait {
        test as private test1;
        SecondTrait::testAmbiguous insteadof testAmbiguous;
        FirstTrait::testAmbiguousAsWell insteadof testAmbiguousAsWell;
    }

    protected function overriddenInChild()
    {

    }
}
