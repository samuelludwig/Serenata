<?php

namespace Serenata\Tests\Unit\Parsing;

use PhpParser\Node;
use PhpParser\Lexer;
use PhpParser\ParserFactory;

use PHPUnit\Framework\TestCase;

use Serenata\Parsing\PartialParser;
use Serenata\Parsing\ParserTokenHelper;
use Serenata\Parsing\LastExpressionParser;

final class LastExpressionParserTest extends TestCase
{
    /**
     * @return ParserFactory
     */
    private function createParserFactoryStub(): ParserFactory
    {
        return new ParserFactory();
    }

    /**
     * @return PartialParser
     */
    private function createPartialParserStub(): PartialParser
    {
        return new PartialParser($this->createParserFactoryStub(), new Lexer());
    }

    /**
     * @return ParserTokenHelper
     */
    private function createParserTokenHelperStub(): ParserTokenHelper
    {
        return new ParserTokenHelper();
    }

    /**
     * @return LastExpressionParser
     */
    private function createLastExpressionParser(): LastExpressionParser
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ConstFetch::class, $result->expr);
        self::assertSame('array_walk', $result->expr->name->toString());
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

            Bar::TEST_CONSTANT
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        self::assertInstanceOf(Node\Name::class, $result->expr->class);
        self::assertSame('Bar', $result->expr->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('TEST_CONSTANT', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testSeesDoInClassConstFetchAsClassConstFetch(): void
    {
        $source = <<<'SOURCE'
            <?php

            Bar::DO
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        self::assertInstanceOf(Node\Name::class, $result->expr->class);
        self::assertSame('Bar', $result->expr->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('DO', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testSeesNewInClassConstFetchAsClassConstFetch(): void
    {
        $source = <<<'SOURCE'
            <?php

            self::NEW
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        self::assertInstanceOf(Node\Name::class, $result->expr->class);
        self::assertSame('self', $result->expr->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('NEW', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\StaticCall::class, $result->expr);
        self::assertInstanceOf(Node\Name::class, $result->expr->class);
        self::assertSame('NamespaceTest\Bar', $result->expr->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('staticmethod', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('someProperty', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testStopsAtYieldKeyword(): void
    {
        $source = <<<'SOURCE'
            <?php

            yield $this->someProperty
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('someProperty', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testStopsAtYieldKeyword2(): void
    {
        $source = <<<'SOURCE'
            <?php

            yield from $this->someProperty
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('someProperty', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('someProperty', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->expr->var);
        self::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        self::assertSame('self', $result->expr->var->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->var->name);
        self::assertSame('someProperty', $result->expr->var->name->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('test', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->expr->var);
        self::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        self::assertSame('parent', $result->expr->var->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->var->name);
        self::assertSame('someProperty', $result->expr->var->name->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('test', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->expr->var);
        self::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        self::assertSame('static', $result->expr->var->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->var->name);
        self::assertSame('someProperty', $result->expr->var->name->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('test', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('c', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('foo', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('d', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('bar', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('c', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('bar', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        self::assertSame('.:', $result->expr->value);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var->var);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var->name);
        self::assertSame('this', $result->expr->var->var->name);
        self::assertSame('foo', $result->expr->var->name->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('test', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('test', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        self::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        self::assertSame('Foo\Bar', $result->expr->var->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('doFoo', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        self::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        self::assertSame('Foo\Bar', $result->expr->var->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('doFoo', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        self::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        self::assertSame('Foo\Bar', $result->expr->var->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('doFoo', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        self::assertInstanceOf(Node\Name::class, $result->expr->var->class);
        self::assertSame('Foo\Bar', $result->expr->var->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('doFoo', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var->var);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var->var->var);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var->var->var->var);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->var->name);
        self::assertSame('testChaining', $result->expr->var->name->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->var->var->name);
        self::assertSame('testChaining', $result->expr->var->var->name->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->var->var->var->name);
        self::assertSame('testChaining', $result->expr->var->var->var->name->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->var->var->var->var->name);
        self::assertSame('testChaining', $result->expr->var->var->var->var->name->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('testChai', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        self::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[0]);
        self::assertSame('((', $result->expr->parts[0]->value);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[1]);
        self::assertSame('version', $result->expr->parts[1]->name);
        self::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[2]);
        self::assertSame('{0} * 10000) + (', $result->expr->parts[2]->value);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[3]);
        self::assertSame('version', $result->expr->parts[3]->name);
        self::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[4]);
        self::assertSame('{2} * 100) + ', $result->expr->parts[4]->value);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[5]);
        self::assertSame('version', $result->expr->parts[5]->name);
        self::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[6]);
        self::assertSame('{4}', $result->expr->parts[6]->value);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->parts[0]);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[0]->var);
        self::assertSame('test', $result->expr->parts[0]->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->parts[0]->name);
        self::assertSame('foo', $result->expr->parts[0]->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->parts[0]);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[0]->var);
        self::assertSame('test', $result->expr->parts[0]->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->parts[0]->name);
        self::assertSame('foo', $result->expr->parts[0]->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr->parts[0]);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[0]->var);
        self::assertSame('test', $result->expr->parts[0]->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->parts[0]->name);
        self::assertSame('foo', $result->expr->parts[0]->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        self::assertSame('{$a->asd()[0]}', $result->expr->value);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        self::assertSame('TEST', $result->expr->value);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        self::assertSame('TEST', $result->expr->value);
    }

    /**
     * @return void
     */
    public function testStopsAtHeredocContainingInterpolatedValues(): void
    {
        $source = <<<'SOURCE'
<?php

<<<BLOCK
EOF: {$foo[2]->bar()} some_text

This is / some text.

BLOCK
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        self::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[0]);
        self::assertSame('EOF: ', $result->expr->parts[0]->value);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->parts[1]);
        self::assertInstanceOf(Node\Expr\ArrayDimFetch::class, $result->expr->parts[1]->var);

        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->parts[1]->var->var);
        self::assertSame('foo', $result->expr->parts[1]->var->var->name);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->parts[1]->var->dim);
        self::assertSame(2, $result->expr->parts[1]->var->dim->value);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->parts[1]->name);
        self::assertSame('bar', $result->expr->parts[1]->name->name);
        self::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[2]);
        self::assertSame(" some_text\n\nThis is / some text.\n", $result->expr->parts[2]->value);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        self::assertInstanceOf(Node\Name::class, $result->expr->class);
        self::assertSame('Test', $result->expr->class->toString());
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('class', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr);
        self::assertSame(0, $result->expr->value);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
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

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testStopsWhenEncounteringClosingParenthesisWhenJustHavingSeenASymbolThatCannotBePrecededByIt(): void
    {
        $source = <<<'SOURCE'
            <?php

            array_merge()
            $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testStopsWhenEncounteringClosingSquareBracketWhenJustHavingSeenASymbolThatCannotBePrecededByIt(): void
    {
        $source = <<<'SOURCE'
            <?php

            []
            $this->one
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('one', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testIncludesColonForClosureReturnTypeInsideExpression(): void
    {
        $source = <<<'SOURCE'
            <?php

            map(function (): array {})->
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\FuncCall::class, $result->expr->var);
        self::assertInstanceOf(Node\Name::class, $result->expr->var->name);
        self::assertSame('map', $result->expr->var->name->toString());
    }

    /**
     * @return void
     */
    public function testParsesExpressionIfPreviousExpressionIsNotTerminatedBySemicolon(): void
    {
        $source = <<<'SOURCE'
            <?php

            $a->foo
            $b->
SOURCE;

        $result = $this->createLastExpressionParser()->getLastNodeAt($source);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('b', $result->expr->var->name);
    }
}
