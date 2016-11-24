<?php

namespace PhpIntegrator\Test\Parsing;

use PhpIntegrator\Parsing\PartialParser;

use PhpParser\Node;

class PartialParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtBasicFunctionCalls()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            array_walk
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\ConstFetch::class, $result);
        $this->assertEquals('array_walk', $result->name->toString());
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtStaticClassNames()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            Bar::testProperty
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\ClassConstFetch::class, $result);
        $this->assertEquals('Bar', $result->class->toString());
        $this->assertEquals('testProperty', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtStaticClassNamesContainingANamespace()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            NamespaceTest\Bar::staticmethod()
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\StaticCall::class, $result);
        $this->assertEquals('NamespaceTest\Bar', $result->class->toString());
        $this->assertEquals('staticmethod', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtControlKeywords()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            return $this->someProperty
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('someProperty', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtBuiltinConstructs()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            echo $this->someProperty
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('someProperty', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtKeywordsSuchAsSelfAndParent()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            if(true) {

            }

            self::$someProperty->test
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->var);
        $this->assertEquals('self', $result->var->class);
        $this->assertEquals('someProperty', $result->var->name);
        $this->assertEquals('test', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtTernaryOperatorsFirstOperand()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $a = $b ? $c->foo()
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertEquals('c', $result->var->name);
        $this->assertEquals('foo', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtTernaryOperatorsLastOperand()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $a = $b ? $c->foo() : $d->bar()
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertEquals('d', $result->var->name);
        $this->assertEquals('bar', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtConcatenationOperators()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $a = $b . $c->bar()
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertEquals('c', $result->var->name);
        $this->assertEquals('bar', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtReadsStringWithDotsAndColonsInIt()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $a = '.:'
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\String_::class, $result);
        $this->assertEquals('.:', $result->value);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsWhenTheBracketSyntaxIsUsedForDynamicAccessToMembers()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            $this->{$foo}()->test()
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result->var);
        $this->assertInstanceOf(Node\Expr\Variable::class, $result->var->var);
        $this->assertInstanceOf(Node\Expr\Variable::class, $result->var->name);
        $this->assertEquals('this', $result->var->var->name);
        $this->assertEquals('foo', $result->var->name->name);
        $this->assertEquals('test', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtCasts()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $test = (int) $this->test
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('test', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsWhenTheBracketSyntaxIsUsedForVariablesInsideStrings()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $test = "
                SELECT *

                FROM {$this->
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtTheNewKeyword()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $test = new $this->
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsWhenTheFirstElementIsAnInstantiationWrappedInParantheses()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            (new Foo\Bar())->doFoo()
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertInstanceOf(Node\Expr\New_::class, $result->var);
        $this->assertEquals('Foo\Bar', $result->var->class);
        $this->assertEquals('doFoo', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsWhenTheFirstElementIsAnInstantiationAsArrayValueInAKeyValuePair()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $test = [
                'test' => (new Foo\Bar())->doFoo()
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertInstanceOf(Node\Expr\New_::class, $result->var);
        $this->assertEquals('Foo\Bar', $result->var->class);
        $this->assertEquals('doFoo', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsWhenTheFirstElementIsAnInstantiationWrappedInParaenthesesAndItIsInsideAnArray()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $array = [
                (new Foo\Bar())->doFoo()
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertInstanceOf(Node\Expr\New_::class, $result->var);
        $this->assertEquals('Foo\Bar', $result->var->class);
        $this->assertEquals('doFoo', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsWhenTheFirstElementInAnInstantiationWrappedInParanthesesAndItIsInsideAFunctionCall()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            foo(firstArg($test), (new Foo\Bar())->doFoo()
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertInstanceOf(Node\Expr\New_::class, $result->var);
        $this->assertEquals('Foo\Bar', $result->var->class);
        $this->assertEquals('doFoo', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtSanitizesComplexCallStack()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $this
                ->testChaining(5, ['Somewhat more complex parameters', /* inline comment */ null])
                //------------
                /*
                    another comment$this;[]{}**** /*int echo return
                */
                ->testChaining(2, [
                //------------
                    'value1',
                    'value2'
                ])

                ->testChaining(
                //------------
                    3,
                    [],
                    function (FooClass $foo) {
                        echo 'test';
                        //    --------
                        return $foo;
                    }
                )

                ->testChaining(
                //------------
                    nestedCall() - (2 * 5),
                    nestedCall() - 3
                )

                ->testChai
SOURCE;

        $expectedResult = ['$this', 'testChaining()', 'testChaining()', 'testChaining()', 'testChaining()', 'testChai'];

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result->var);
        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result->var->var);
        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result->var->var->var);
        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result->var->var->var->var);
        $this->assertEquals('testChaining', $result->var->name);
        $this->assertEquals('testChaining', $result->var->var->name);
        $this->assertEquals('testChaining', $result->var->var->var->name);
        $this->assertEquals('testChaining', $result->var->var->var->var->name);
        $this->assertEquals('testChai', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtSanitizesStaticCallWithStaticKeyword()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            static::doSome
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\ClassConstFetch::class, $result);
        $this->assertEquals('static', $result->class);
        $this->assertEquals('doSome', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtAssignmentSymbol()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            $test = $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtMultiplicationOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 * $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtDivisionOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 / $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtPlusOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 + $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtModulusOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 % $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtMinusOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 - $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtBitwisoOrOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 | $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtBitwiseAndOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 & $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtBitwiseXorOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 ^ $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtBitwiseNotOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 ~ $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtBooleanLessOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 < $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtBooleanGreaterOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            5 < $this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtBooleanNotOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            !$this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsAtSilencingOperator()
    {
        $partialParser = new PartialParser();

        $source = <<<'SOURCE'
            <?php

            @$this->one
SOURCE;

        $result = $partialParser->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithSingleLineInvocation()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//             <?php
//
//             $this->test(1, 2, 3
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(42, $result['offset']);
//         $this->assertEquals(['$this', 'test'], $result['callStack']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithMultiLineInvocation()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         $this->test(
//             1,
//             2,
//             3
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(34, $result['offset']);
//         $this->assertEquals(['$this', 'test'], $result['callStack']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithMoreComplexNestedArguments1()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         builtin_func(
//             ['test', $this->foo()],
//             function ($a) {
//                 // Something here.
//                 $this->something();
//             },
//             3
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(35, $result['offset']);
//         $this->assertEquals(['builtin_func'], $result['callStack']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithMoreComplexNestedArguments2()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         builtin_func(/* test */
//             "]",// a comment
//             "}",/*}*/
//             ['test'
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(35, $result['offset']);
//         $this->assertEquals(['builtin_func'], $result['callStack']);
//         $this->assertEquals('function', $result['type']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithMoreComplexNestedArguments3()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         builtin_func(
//             $this->foo(),
//             $array['key'],
//             $array['ke
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(35, $result['offset']);
//         $this->assertEquals(['builtin_func'], $result['callStack']);
//         $this->assertEquals('function', $result['type']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithTrailingCommas()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         builtin_func(
//             foo(),
//             [
//                 'Trailing comma',
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(1, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithNestedParantheses()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         builtin_func(
//             foo(),
//             ($a + $b
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(35, $result['offset']);
//         $this->assertEquals(['builtin_func'], $result['callStack']);
//         $this->assertEquals('function', $result['type']);
//         $this->assertEquals(1, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithSqlStringArguments()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         foo("SELECT a.one, a.two, a.three FROM test", second
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(1, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithSqlStringArgumentsContainingParantheses()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         foo('IF(
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(['foo'], $result['callStack']);
//         $this->assertEquals('function', $result['type']);
//         $this->assertEquals(0, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithConstructorCallsWithNormalClassName()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         new MyObject(
//             1,
//             2,
//             3
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(35, $result['offset']);
//         $this->assertEquals(['MyObject'], $result['callStack']);
//         $this->assertEquals('instantiation', $result['type']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithConstructorCallsWithNormalClassNamePrecededByLeadingSlash()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         new \MyObject(
//             1,
//             2,
//             3
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(36, $result['offset']);
//         $this->assertEquals(['\MyObject'], $result['callStack']);
//         $this->assertEquals('instantiation', $result['type']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithConstructorCallsWithNormalClassNamePrecededByLeadingSlashAndMultipleParts()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         new \MyNamespace\MyObject(
//             1,
//             2,
//             3
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(48, $result['offset']);
//         $this->assertEquals(['\MyNamespace\MyObject'], $result['callStack']);
//         $this->assertEquals('instantiation', $result['type']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithConstructorCalls2()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         new static(
//             1,
//             2,
//             3
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(33, $result['offset']);
//         $this->assertEquals(['static'], $result['callStack']);
//         $this->assertEquals('instantiation', $result['type']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtWithConstructorCalls3()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         new self(
//             1,
//             2,
//             3
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertEquals(31, $result['offset']);
//         $this->assertEquals(['self'], $result['callStack']);
//         $this->assertEquals('instantiation', $result['type']);
//         $this->assertEquals(2, $result['argumentIndex']);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtReturnsNullWhenNotInInvocation1()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         if ($this->test() as $test) {
//             if (true) {
//
//             }
//         }
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertNull($result);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtReturnsNullWhenNotInInvocation2()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         $this->test();
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertNull($result);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtReturnsNullWhenNotInInvocation3()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         function test($a, $b)
//         {
//
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertNull($result);
//     }
//
//     /**
//      * @return void
//      */
//     public function testGetInvocationInfoAtReturnsNullWhenNotInInvocation4()
//     {
//         $partialParser = new PartialParser();
//
//         $source = <<<'SOURCE'
//         <?php
//
//         if (preg_match('/^array\s*\(/', $firstElement) === 1) {
//             $className = 'array';
//         } elseif (
// SOURCE;
//
//         $result = $partialParser->getInvocationInfoAt($source);
//
//         $this->assertNull($result);
//     }
}
