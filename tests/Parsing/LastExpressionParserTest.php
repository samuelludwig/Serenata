<?php

namespace PhpIntegrator\Tests\Parsing;

use PhpIntegrator\Parsing\PartialParser;
use PhpIntegrator\Parsing\PrettyPrinter;
use PhpIntegrator\Parsing\ParserTokenHelper;
use PhpIntegrator\Parsing\LastExpressionParser;

use PhpParser\Node;
use PhpParser\Lexer;
use PhpParser\ParserFactory;

class LastExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return ParserFactory
     */
    protected function createParserFactoryStub(): ParserFactory
    {
        return new ParserFactory();
    }

    /**
     * @return ParserFactory
     */
    protected function createPrettyPrinterStub(): PrettyPrinter
    {
        return new PrettyPrinter();
    }

    /**
     * @return ParserFactory
     */
    protected function createPartialParserStub(): PartialParser
    {
        return new PartialParser($this->createParserFactoryStub(), new Lexer());
    }

    /**
     * @return ParserTokenHelper
     */
    protected function createParserTokenHelperStub(): ParserTokenHelper
    {
        return new ParserTokenHelper();
    }

    /**
     * @return LastExpressionParser
     */
    protected function createLastExpressionParser(): LastExpressionParser
    {
        return new LastExpressionParser(
            $this->createPartialParserStub(),
            $this->createParserTokenHelperStub()
        );
    }

    /**
     * @return void
     */
    public function testStopsAtFunctionCalls(): void
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
    public function testStopsAtStaticClassNames(): void
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
    public function testStopsAtStaticClassNamesContainingANamespace(): void
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
    public function testStopsAtControlKeywords(): void
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
    public function testStopsAtBuiltinConstructs(): void
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
    public function testStopsAtSelfKeywords(): void
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
    public function testStopsAtParentKeyword(): void
    {
        $source = <<<'SOURCE'
            <?php

            if(true) {

            }

            parent::$someProperty->test
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->var);
        $this->assertEquals('parent', $result->var->class);
        $this->assertEquals('someProperty', $result->var->name);
        $this->assertEquals('test', $result->name);
    }

    /**
     * @return void
     */
    public function testStopsAtStaticKeyword(): void
    {
        $source = <<<'SOURCE'
            <?php

            if(true) {

            }

            static::$someProperty->test
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        $this->assertInstanceOf(Node\Expr\PropertyFetch::class, $result);
        $this->assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->var);
        $this->assertEquals('static', $result->var->class);
        $this->assertEquals('someProperty', $result->var->name);
        $this->assertEquals('test', $result->name);
    }

    /**
     * @return void
     */
    public function testStopsAtTernaryOperatorFirstOperand(): void
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
    public function testStopsAtTernaryOperatorLastOperand(): void
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
    public function testStopsAtConcatenationOperator(): void
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
    public function testStopsAtString(): void
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
    public function testStopsAtMethodCallWIthDynamicMemberAccess(): void
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
    public function testStopsAtCasts(): void
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
    public function testStopsAtMemberCallInsideInterpolation(): void
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
    public function testStopsAtNewKeyword(): void
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
    public function testStopsAtMethodCallWithNewInstantiationInParantheses(): void
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
    public function testStopsAtMethodCallWithNewInstantiationInParanthesesAsArrayValue(): void
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
    public function testStopsAtMethodCallWithNewInstantiationInParanthesesAsArrayElement(): void
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
    public function testStopsAtMethodCallWithNewInstantiationInParanthesesAsSecondFunctionArgument(): void
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
    public function testStopsAtComplexMethodCall(): void
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
    public function testStopsAtPropertyFetchInAssignment(): void
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
    public function testStopsAtEncapsedString(): void
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
    public function testStopsAtEncapsedStringWithInterpolatedMethodCall(): void
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
    public function testGetLastNodeAtCorrectlyDealsWithEncapsedStringWithIntepolatedMethodCallAndParentheses(): void
    {
        $source = <<<'SOURCE'
            <?php

            ("{$test->foo()}")
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
    public function testStopsAtEncapsedStringWithInterpolatedPropertyFetch(): void
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
    public function testStopsAtStringContainingIgnoredInterpolations(): void
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
    public function testStopsAtNowdoc(): void
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
    public function testStopsAtHeredoc(): void
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
    public function testStopsAtHeredocContainingInterpolatedValues(): void
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
    public function testStopsAtPropertyFetchAfterHeredoc(): void
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
    public function testStopsAtSpecialClassConstantClassKeyword(): void
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
    public function testStopsAtMultiplicationOperator(): void
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
    public function testStopsAtDivisionOperator(): void
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
    public function testStopsAtPlusOperator(): void
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
    public function testStopsAtModulusOperator(): void
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
    public function testStopsAtMinusOperator(): void
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
    public function testStopsAtBitwisoOrOperator(): void
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
    public function testStopsAtBitwiseAndOperator(): void
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
    public function testStopsAtBitwiseXorOperator(): void
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
    public function testStopsAtBitwiseNotOperator(): void
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
    public function testStopsAtBooleanLessOperator(): void
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
    public function testStopsAtBooleanGreaterOperator(): void
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
    public function testStopsAtShiftLeftOperator(): void
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
    public function testStopsAtShiftRightOperator(): void
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
    public function testStopsAtShiftLeftExpressionWithZeroAsRightOperand(): void
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
    public function testStopsAtBooleanNotOperator(): void
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
    public function testStopsAtSilencingOperator(): void
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
}
