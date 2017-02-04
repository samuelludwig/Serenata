<?php

namespace PhpIntegrator\Tests\Parsing;

use PhpIntegrator\Parsing\PartialParser;
use PhpIntegrator\Parsing\PrettyPrinter;
use PhpIntegrator\Parsing\LastExpressionParser;

use PhpParser\Node;
use PhpParser\ParserFactory;

class LastExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return ParserFactory
     */
    protected function createParserFactoryStub()
    {
        return new ParserFactory();
    }

    /**
     * @return ParserFactory
     */
    protected function createPrettyPrinterStub()
    {
        return new PrettyPrinter();
    }

    /**
     * @return ParserFactory
     */
    protected function createPartialParserStub()
    {
        return new PartialParser($this->createParserFactoryStub());
    }

    /**
     * @return LastExpressionParser
     */
    protected function createLastExpressionParser()
    {
        return new LastExpressionParser($this->createPartialParserStub(), $this->createPrettyPrinterStub());
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithFunctionCalls()
    {
        $source = <<<'SOURCE'
            <?php

            array_walk
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\ConstFetch::class, $result);
        $this->assertEquals('array_walk', $result->name->toString());
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithStaticClassNames()
    {
        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            Bar::testProperty
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\ClassConstFetch::class, $result);
        $this->assertEquals('Bar', $result->class->toString());
        $this->assertEquals('testProperty', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithStaticClassNamesContainingANamespace()
    {
        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            NamespaceTest\Bar::staticmethod()
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\StaticCall::class, $result);
        $this->assertEquals('NamespaceTest\Bar', $result->class->toString());
        $this->assertEquals('staticmethod', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithControlKeywords()
    {
        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            return $this->someProperty
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('someProperty', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithBuiltinConstructs()
    {
        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            echo $this->someProperty
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('someProperty', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithKeywordsSuchAsSelfAndParent()
    {
        $source = <<<'SOURCE'
            <?php

            if(true) {

            }

            self::$someProperty->test
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->var);
        $this->assertEquals('self', $result->var->class);
        $this->assertEquals('someProperty', $result->var->name);
        $this->assertEquals('test', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithTernaryOperatorsFirstOperand()
    {
        $source = <<<'SOURCE'
            <?php

            $a = $b ? $c->foo()
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertEquals('c', $result->var->name);
        $this->assertEquals('foo', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithTernaryOperatorsLastOperand()
    {
        $source = <<<'SOURCE'
            <?php

            $a = $b ? $c->foo() : $d->bar()
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertEquals('d', $result->var->name);
        $this->assertEquals('bar', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithConcatenationOperators()
    {
        $source = <<<'SOURCE'
            <?php

            $a = $b . $c->bar()
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result);
        $this->assertEquals('c', $result->var->name);
        $this->assertEquals('bar', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtReadsStringWithDotsAndColonsInIt()
    {
        $source = <<<'SOURCE'
            <?php

            $a = '.:'
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\String_::class, $result);
        $this->assertEquals('.:', $result->value);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsWhenTheBracketSyntaxIsUsedForDynamicAccessToMembers()
    {
        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            $this->{$foo}()->test()
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

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
    public function testGetLastNodeAtCorrectlyDealsWithCasts()
    {
        $source = <<<'SOURCE'
            <?php

            $test = (int) $this->test
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('test', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsWhenTheBracketSyntaxIsUsedForVariablesInsideStrings()
    {
        $source = <<<'SOURCE'
            <?php

            $test = "
                SELECT *

                FROM {$this->
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithTheNewKeyword()
    {
        $source = <<<'SOURCE'
            <?php

            $test = new $this->
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtStopsWhenTheFirstElementIsAnInstantiationWrappedInParantheses()
    {
        $source = <<<'SOURCE'
            <?php

            if (true) {
                // More code here.
            }

            (new Foo\Bar())->doFoo()
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

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
        $source = <<<'SOURCE'
            <?php

            $test = [
                'test' => (new Foo\Bar())->doFoo()
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

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
        $source = <<<'SOURCE'
            <?php

            $array = [
                (new Foo\Bar())->doFoo()
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

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
        $source = <<<'SOURCE'
            <?php

            foo(firstArg($test), (new Foo\Bar())->doFoo()
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

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

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

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
        $source = <<<'SOURCE'
            <?php

            static::doSome
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\ClassConstFetch::class, $result);
        $this->assertEquals('static', $result->class);
        $this->assertEquals('doSome', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithAssignmentSymbol()
    {
        $source = <<<'SOURCE'
            <?php

            $test = $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithEncapsedString()
    {
        $source = <<<'SOURCE'
            <?php

            "(($version{0} * 10000) + ($version{2} * 100) + $version{4}"
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\Encapsed::class, $result);
        $this->assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->parts[0]);
        $this->assertEquals('((', $result->parts[0]->value);
        $this->assertInstanceOf(Node\Expr\Variable::class, $result->parts[1]);
        $this->assertEquals('version', $result->parts[1]->name);
        $this->assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->parts[2]);
        $this->assertEquals('{0} * 10000) + (', $result->parts[2]->value);
        $this->assertInstanceOf(Node\Expr\Variable::class, $result->parts[3]);
        $this->assertEquals('version', $result->parts[3]->name);
        $this->assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->parts[4]);
        $this->assertEquals('{2} * 100) + ', $result->parts[4]->value);
        $this->assertInstanceOf(Node\Expr\Variable::class, $result->parts[5]);
        $this->assertEquals('version', $result->parts[5]->name);
        $this->assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->parts[6]);
        $this->assertEquals('{4}', $result->parts[6]->value);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithEncapsedStringWithIntepolatedMethodCall()
    {
        $source = <<<'SOURCE'
            <?php

            "{$test->foo()}"
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\Encapsed::class, $result);
        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result->parts[0]);
        $this->assertEquals('test', $result->parts[0]->var->name);
        $this->assertEquals('foo', $result->parts[0]->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithEncapsedStringWithIntepolatedPropertyFetch()
    {
        $source = <<<'SOURCE'
            <?php

            "{$test->foo}"
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\Encapsed::class, $result);
        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result->parts[0]);
        $this->assertEquals('test', $result->parts[0]->var->name);
        $this->assertEquals('foo', $result->parts[0]->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithStringContainingIgnoredInterpolations()
    {
        $source = <<<'SOURCE'
            <?php

            '{$a->asd()[0]}'
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\String_::class, $result);
        $this->assertEquals('{$a->asd()[0]}', $result->value);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithNowdoc()
    {
        $source = <<<'SOURCE'
<?php

<<<'EOF'
TEST
EOF
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\String_::class, $result);
        $this->assertEquals('TEST', $result->value);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithHeredoc()
    {
        $source = <<<'SOURCE'
<?php

<<<EOF
TEST
EOF
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\String_::class, $result);
        $this->assertEquals('TEST', $result->value);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithHeredocContainingInterpolatedValues()
    {
        $source = <<<'SOURCE'
<?php

<<<EOF
EOF: {$foo[2]->bar()} some_text

This is / some text.

EOF
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\Encapsed::class, $result);
        $this->assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->parts[0]);
        $this->assertEquals('EOF: ', $result->parts[0]->value);
        $this->assertInstanceOf(Node\Expr\MethodCall::class, $result->parts[1]);
        $this->assertInstanceOf(Node\Expr\ArrayDimFetch::class, $result->parts[1]->var);
        $this->assertEquals('foo', $result->parts[1]->var->var->name);
        $this->assertEquals(2, $result->parts[1]->var->dim->value);
        $this->assertEquals('bar', $result->parts[1]->name);
        $this->assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->parts[2]);
        $this->assertEquals(" some_text\n\nThis is / some text.\n", $result->parts[2]->value);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithHeredocFollowedByThisAccess()
    {
        $source = <<<'SOURCE'
<?php

define('TEST', <<<TEST
TEST
);

$this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithSpecialClassConstantClassKeyword()
    {
        $source = <<<'SOURCE'
<?php

Test::class
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\ClassConstFetch::class, $result);
        $this->assertEquals('Test', $result->class->toString());
        $this->assertEquals('class', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithMultiplicationOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 * $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithDivisionOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 / $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithPlusOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 + $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithModulusOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 % $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithMinusOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 - $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithBitwisoOrOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 | $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithBitwiseAndOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 & $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithBitwiseXorOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 ^ $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithBitwiseNotOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 ~ $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithBooleanLessOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 < $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithBooleanGreaterOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 > $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithShiftLeftOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 << $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithShiftRightOperator()
    {
        $source = <<<'SOURCE'
            <?php

            5 >> $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithShiftLeftExpressionWithAZeroAsRightOperand()
    {
        $source = <<<'SOURCE'
            <?php

            1 << 0
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Scalar\LNumber::class, $result);
        $this->assertEquals(0, $result->value);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithBooleanNotOperator()
    {
        $source = <<<'SOURCE'
            <?php

            !$this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetLastNodeAtCorrectlyDealsWithSilencingOperator()
    {
        $source = <<<'SOURCE'
            <?php

            @$this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertEquals('this', $result->var->name);
        $this->assertEquals('one', $result->name);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithSingleLineInvocation()
    {
        $source = <<<'SOURCE'
            <?php

            $this->test(1, 2, 3
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(42, $result['offset']);
        $this->assertEquals('test', $result['name']);
        $this->assertEquals('$this->test', $result['expression']);
        $this->assertEquals('method', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithMultiLineInvocation()
    {
        $source = <<<'SOURCE'
        <?php

        $this->test(
            1,
            2,
            3
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(34, $result['offset']);
        $this->assertEquals('test', $result['name']);
        $this->assertEquals('$this->test', $result['expression']);
        $this->assertEquals('method', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithMoreComplexNestedArguments1()
    {
        $source = <<<'SOURCE'
        <?php

        builtin_func(
            ['test', $this->foo()],
            function ($a) {
                // Something here.
                $this->something();
            },
            3
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(35, $result['offset']);
        $this->assertEquals('builtin_func', $result['name']);
        $this->assertEquals('builtin_func', $result['expression']);
        $this->assertEquals('function', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithMoreComplexNestedArguments2()
    {
        $source = <<<'SOURCE'
        <?php

        builtin_func(/* test */
            "]",// a comment
            "}",/*}*/
            ['test'
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(35, $result['offset']);
        $this->assertEquals('builtin_func', $result['name']);
        $this->assertEquals('builtin_func', $result['expression']);
        $this->assertEquals('function', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithMoreComplexNestedArguments3()
    {
        $source = <<<'SOURCE'
        <?php

        builtin_func(
            $this->foo(),
            $array['key'],
            $array['ke
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(35, $result['offset']);
        $this->assertEquals('builtin_func', $result['name']);
        $this->assertEquals('builtin_func', $result['expression']);
        $this->assertEquals('function', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithTrailingCommas()
    {
        $source = <<<'SOURCE'
        <?php

        builtin_func(
            foo(),
            [
                'Trailing comma',
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(1, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithNestedParantheses()
    {
        $source = <<<'SOURCE'
        <?php

        builtin_func(
            foo(),
            ($a + $b
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(35, $result['offset']);
        $this->assertEquals('builtin_func', $result['name']);
        $this->assertEquals('builtin_func', $result['expression']);
        $this->assertEquals('function', $result['type']);
        $this->assertEquals(1, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithSqlStringArguments()
    {
        $source = <<<'SOURCE'
        <?php

        foo("SELECT a.one, a.two, a.three FROM test", second
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(1, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithSqlStringArgumentsContainingParantheses()
    {
        $source = <<<'SOURCE'
        <?php

        foo('IF(
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals('foo', $result['name']);
        $this->assertEquals('foo', $result['expression']);
        $this->assertEquals('function', $result['type']);
        $this->assertEquals(0, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithConstructorCallsWithNormalClassName()
    {
        $source = <<<'SOURCE'
        <?php

        new MyObject(
            1,
            2,
            3
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(35, $result['offset']);
        $this->assertEquals('MyObject', $result['name']);
        $this->assertEquals('MyObject', $result['expression']);
        $this->assertEquals('instantiation', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithConstructorCallsWithNormalClassNamePrecededByLeadingSlash()
    {
        $source = <<<'SOURCE'
        <?php

        new \MyObject(
            1,
            2,
            3
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(36, $result['offset']);
        $this->assertEquals('\MyObject', $result['name']);
        $this->assertEquals('\MyObject', $result['expression']);
        $this->assertEquals('instantiation', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithConstructorCallsWithNormalClassNamePrecededByLeadingSlashAndMultipleParts()
    {
        $source = <<<'SOURCE'
        <?php

        new \MyNamespace\MyObject(
            1,
            2,
            3
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(48, $result['offset']);
        $this->assertEquals('\MyNamespace\MyObject', $result['name']);
        $this->assertEquals('\MyNamespace\MyObject', $result['expression']);
        $this->assertEquals('instantiation', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithConstructorCalls2()
    {
        $source = <<<'SOURCE'
        <?php

        new static(
            1,
            2,
            3
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(33, $result['offset']);
        $this->assertEquals('static', $result['name']);
        $this->assertEquals('static', $result['expression']);
        $this->assertEquals('instantiation', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtWithConstructorCalls3()
    {
        $source = <<<'SOURCE'
        <?php

        new self(
            1,
            2,
            3
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertEquals(31, $result['offset']);
        $this->assertEquals('self', $result['name']);
        $this->assertEquals('self', $result['expression']);
        $this->assertEquals('instantiation', $result['type']);
        $this->assertEquals(2, $result['argumentIndex']);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtReturnsNullWhenNotInInvocation1()
    {
        $source = <<<'SOURCE'
        <?php

        if ($this->test() as $test) {
            if (true) {

            }
        }
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertNull($result);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtReturnsNullWhenNotInInvocation2()
    {
        $source = <<<'SOURCE'
        <?php

        $this->test();
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertNull($result);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtReturnsNullWhenNotInInvocation3()
    {
        $source = <<<'SOURCE'
        <?php

        function test($a, $b)
        {

SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertNull($result);
    }

    /**
     * @return void
     */
    public function testGetInvocationInfoAtReturnsNullWhenNotInInvocation4()
    {
        $source = <<<'SOURCE'
        <?php

        if (preg_match('/^array\s*\(/', $firstElement) === 1) {
            $className = 'array';
        } elseif (
SOURCE;

        $result = $this->createLastExpressionParser()->getInvocationInfoAt($source);

        $this->assertNull($result);
    }
}
