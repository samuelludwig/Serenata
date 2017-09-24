<?php

namespace PhpIntegrator\Tests\Unit\Parsing;

use PhpIntegrator\Parsing\PartialParser;
use PhpIntegrator\Parsing\PrettyPrinter;
use PhpIntegrator\Parsing\ParserTokenHelper;
use PhpIntegrator\Parsing\LastExpressionParser;

use PhpParser\Node;
use PhpParser\Lexer;
use PhpParser\ParserFactory;

class LastExpressionParserTest extends \PHPUnit\Framework\TestCase
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\ConstFetch::class, $result->expr);
        static::assertSame('array_walk', $result->expr->name->toString());
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        static::assertSame('Bar', $result->expr->class->toString());
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('testProperty', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\StaticCall::class, $result->expr);
        static::assertSame('NamespaceTest\Bar', $result->expr->class->toString());
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('staticmethod', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('someProperty', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('someProperty', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->expr->var);
        static::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        static::assertSame('self', $result->expr->var->class->toString());
        static::assertInstanceOf(Node\Identifier::class, $result->expr->var->name);
        static::assertSame('someProperty', $result->expr->var->name->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('test', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->expr->var);
        static::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        static::assertSame('parent', $result->expr->var->class->toString());
        static::assertInstanceOf(Node\Identifier::class, $result->expr->var->name);
        static::assertSame('someProperty', $result->expr->var->name->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('test', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->expr->var);
        static::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        static::assertSame('static', $result->expr->var->class->toString());
        static::assertInstanceOf(Node\Identifier::class, $result->expr->var->name);
        static::assertSame('someProperty', $result->expr->var->name->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('test', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertSame('c', $result->expr->var->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('foo', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertSame('d', $result->expr->var->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('bar', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertSame('c', $result->expr->var->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('bar', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        static::assertSame('.:', $result->expr->value);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var->var);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var->name);
        static::assertSame('this', $result->expr->var->var->name);
        static::assertSame('foo', $result->expr->var->name->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('test', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('test', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        static::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        static::assertSame('Foo\Bar', $result->expr->var->class->toString());
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('doFoo', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        static::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        static::assertSame('Foo\Bar', $result->expr->var->class->toString());
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('doFoo', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        static::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        static::assertSame('Foo\Bar', $result->expr->var->class->toString());
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('doFoo', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        static::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        static::assertSame('Foo\Bar', $result->expr->var->class->toString());
        static::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        static::assertSame('doFoo', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var->var);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var->var->var);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var->var->var->var);
        static::assertSame('testChaining', $result->expr->var->name->name);
        static::assertSame('testChaining', $result->expr->var->var->name->name);
        static::assertSame('testChaining', $result->expr->var->var->var->name->name);
        static::assertSame('testChaining', $result->expr->var->var->var->var->name->name);
        static::assertSame('testChai', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        static::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[0]);
        static::assertSame('((', $result->expr->parts[0]->value);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[1]);
        static::assertSame('version', $result->expr->parts[1]->name);
        static::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[2]);
        static::assertSame('{0} * 10000) + (', $result->expr->parts[2]->value);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[3]);
        static::assertSame('version', $result->expr->parts[3]->name);
        static::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[4]);
        static::assertSame('{2} * 100) + ', $result->expr->parts[4]->value);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[5]);
        static::assertSame('version', $result->expr->parts[5]->name);
        static::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[6]);
        static::assertSame('{4}', $result->expr->parts[6]->value);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->parts[0]);
        static::assertSame('test', $result->expr->parts[0]->var->name);
        static::assertSame('foo', $result->expr->parts[0]->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->parts[0]);
        static::assertSame('test', $result->expr->parts[0]->var->name);
        static::assertSame('foo', $result->expr->parts[0]->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr->parts[0]);
        static::assertSame('test', $result->expr->parts[0]->var->name);
        static::assertSame('foo', $result->expr->parts[0]->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        static::assertSame('{$a->asd()[0]}', $result->expr->value);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        static::assertSame('TEST', $result->expr->value);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        static::assertSame('TEST', $result->expr->value);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        static::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[0]);
        static::assertSame('EOF: ', $result->expr->parts[0]->value);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->parts[1]);
        static::assertInstanceOf(Node\Expr\ArrayDimFetch::class, $result->expr->parts[1]->var);
        static::assertSame('foo', $result->expr->parts[1]->var->var->name);
        static::assertSame(2, $result->expr->parts[1]->var->dim->value);
        static::assertSame('bar', $result->expr->parts[1]->name->name);
        static::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[2]);
        static::assertSame(" some_text\n\nThis is / some text.\n", $result->expr->parts[2]->value);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        static::assertSame('Test', $result->expr->class->toString());
        static::assertSame('class', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr);
        static::assertSame(0, $result->expr->value);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
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

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('one', $result->expr->name->name);
    }
}
