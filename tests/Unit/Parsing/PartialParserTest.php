<?php

namespace Serenata\Tests\Unit\Parsing;

use Serenata\Parsing\PartialParser;

use Serenata\Parsing\Node\Expr;

use PhpParser\Node;
use PhpParser\Lexer;
use PhpParser\ParserFactory;

class PartialParserTest extends \PHPUnit\Framework\TestCase
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
    private function createPartialParser(): PartialParser
    {
        return new PartialParser($this->createParserFactoryStub(), new Lexer());
    }

    /**
     * @return void
     */
    public function testParsesFunctionCalls(): void
    {
        $source = <<<'SOURCE'
<?php

array_walk
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\ConstFetch::class, $result->expr);
        static::assertSame('array_walk', $result->expr->name->toString());
    }

    /**
     * @return void
     */
    public function testParsesStaticConstFetches(): void
    {
        $source = <<<'SOURCE'
<?php

Bar::TEST_CONSTANT
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        static::assertSame('Bar', $result->expr->class->toString());
        static::assertSame('TEST_CONSTANT', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesStaticMethodCallsWithNamespacedClassNames(): void
    {
        $source = <<<'SOURCE'
<?php

NamespaceTest\Bar::staticmethod()
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\StaticCall::class, $result->expr);
        static::assertSame('NamespaceTest\Bar', $result->expr->class->toString());
        static::assertSame('staticmethod', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesPropertyFetches(): void
    {
        $source = <<<'SOURCE'
<?php

$this->someProperty
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('someProperty', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesStaticPropertyFetches(): void
    {
        $source = <<<'SOURCE'
<?php

self::$someProperty
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->expr);
        static::assertSame('self', $result->expr->class->toString());
        static::assertSame('someProperty', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesStringWithDotsAndColons(): void
    {
        $source = <<<'SOURCE'
<?php

'.:'
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        static::assertSame('.:', $result->expr->value);
    }

    /**
     * @return void
     */
    public function testParsesDynamicMethodCalls(): void
    {
        $source = <<<'SOURCE'
<?php

$this->{$foo}()->test()
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var->var);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var->name);
        static::assertSame('this', $result->expr->var->var->name);
        static::assertSame('foo', $result->expr->var->name->name);
        static::assertSame('test', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesMemberAccessWithMissingMember(): void
    {
        $source = <<<'SOURCE'
<?php

$this->
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        static::assertSame('this', $result->expr->var->name);
        static::assertSame('', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesMethodCallOnInstantiationInParentheses(): void
    {
        $source = <<<'SOURCE'
<?php

(new Foo\Bar())->doFoo()
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        static::assertSame('Foo\Bar', $result->expr->var->class->toString());
        static::assertSame('doFoo', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesMethodCallOnComplexCallStack(): void
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

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

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
    public function testParsesConstFetchOnStaticKeyword(): void
    {
        $source = <<<'SOURCE'
<?php

static::doSome
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        static::assertSame('static', $result->expr->class->toString());
        static::assertSame('doSome', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesEncapsedString(): void
    {
        $source = <<<'SOURCE'
<?php

"(($version{0} * 10000) + ($version{2} * 100) + $version{4}"
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

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
    public function testParsesEncapsedStringWithIntepolatedMethodCall(): void
    {
        $source = <<<'SOURCE'
<?php

"{$test->foo()}"
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->parts[0]);
        static::assertSame('test', $result->expr->parts[0]->var->name);
        static::assertSame('foo', $result->expr->parts[0]->name->name);
    }

    /**
     * @return void
     */
    public function testParsesEncapsedStringWithIntepolatedPropertyFetch(): void
    {
        $source = <<<'SOURCE'
<?php

"{$test->foo}"
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr->parts[0]);
        static::assertSame('test', $result->expr->parts[0]->var->name);
        static::assertSame('foo', $result->expr->parts[0]->name->name);
    }

    /**
     * @return void
     */
    public function testParsesStringContainingIgnoredInterpolations(): void
    {
        $source = <<<'SOURCE'
<?php

'{$a->asd()[0]}'
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        static::assertSame('{$a->asd()[0]}', $result->expr->value);
    }

    /**
     * @return void
     */
    public function testParsesNowdoc(): void
    {
        $source = <<<'SOURCE'
<?php

<<<'EOF'
TEST
EOF
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        static::assertSame('TEST', $result->expr->value);
    }

    /**
     * @return void
     */
    public function testParsesHeredoc(): void
    {
        $source = <<<'SOURCE'
<?php

<<<EOF
TEST
EOF
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        static::assertSame('TEST', $result->expr->value);
    }

    /**
     * @return void
     */
    public function testParsesHeredocContainingInterpolatedValues(): void
    {
        $source = <<<'SOURCE'
<?php

<<<EOF
EOF: {$foo[2]->bar()} some_text

This is / some text.

EOF
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

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
    public function testParsesConstFetchWithSpecialClassConstantClassKeyword(): void
    {
        $source = <<<'SOURCE'
<?php

Test::class
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        static::assertSame('Test', $result->expr->class->toString());
        static::assertSame('class', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesShiftExpression(): void
    {
        $source = <<<'SOURCE'
<?php

1 << 0
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\BinaryOp\ShiftLeft::class, $result->expr);
        static::assertSame(1, $result->expr->left->value);
        static::assertSame(0, $result->expr->right->value);
    }

    /**
     * @return void
     */
    public function testParsesExpressionWithBooleanNotOperator(): void
    {
        $source = <<<'SOURCE'
<?php

!$this->one
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\BooleanNot::class, $result->expr);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr->expr);
        static::assertSame('this', $result->expr->expr->var->name);
        static::assertSame('one', $result->expr->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesExpressionWithSilencingOperator(): void
    {
        $source = <<<'SOURCE'
<?php

@$this->one
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\ErrorSuppress::class, $result->expr);
        static::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr->expr);
        static::assertSame('this', $result->expr->expr->var->name);
        static::assertSame('one', $result->expr->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesExpressionWithTernaryOperatorWithMissingColon(): void
    {
        $source = <<<'SOURCE'
<?php

$test ? $a
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\Ternary::class, $result->expr);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->cond);
        static::assertSame('test', $result->expr->cond->name);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->if);
        static::assertSame('a', $result->expr->if->name);
        static::assertInstanceOf(Expr\Dummy::class, $result->expr->else);
    }

    /**
     * @return void
     */
    public function testParsesExpressionWithTernaryOperatorWithMissingColonInAssignment(): void
    {
        $source = <<<'SOURCE'
<?php

$b = $test ? $a
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\Assign::class, $result->expr);
        static::assertSame('b', $result->expr->var->name);
        static::assertInstanceOf(Node\Expr\Ternary::class, $result->expr->expr);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->expr->cond);
        static::assertSame('test', $result->expr->expr->cond->name);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->expr->if);
        static::assertSame('a', $result->expr->expr->if->name);
        static::assertInstanceOf(Expr\Dummy::class, $result->expr->expr->else);
    }

    /**
     * @return void
     */
    public function testParsesFunctionCall(): void
    {
        $source = <<<'SOURCE'
<?php

call(1, 2
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\FuncCall::class, $result->expr);
        static::assertSame('call', $result->expr->name->toString());
        static::assertCount(2, $result->expr->args);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        static::assertSame(1, $result->expr->args[0]->value->value);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[1]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[1]->value);
        static::assertSame(2, $result->expr->args[1]->value->value);
    }

    /**
     * @return void
     */
    public function testParsesFunctionCallWithMissingArgument(): void
    {
        $source = <<<'SOURCE'
<?php

call(1,
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\FuncCall::class, $result->expr);
        static::assertSame('call', $result->expr->name->toString());
        static::assertCount(1, $result->expr->args);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        static::assertSame(1, $result->expr->args[0]->value->value);
    }

    /**
     * @return void
     */
    public function testParsesMethodCall(): void
    {
        $source = <<<'SOURCE'
<?php

$this->call(1, 2
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertSame('call', $result->expr->name->name);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        static::assertSame('this', $result->expr->var->name);
        static::assertCount(2, $result->expr->args);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        static::assertSame(1, $result->expr->args[0]->value->value);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[1]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[1]->value);
        static::assertSame(2, $result->expr->args[1]->value->value);
    }

    /**
     * @return void
     */
    public function testParsesMethodCallWithMissingArgument(): void
    {
        $source = <<<'SOURCE'
<?php

$this->call(1,
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        static::assertSame('call', $result->expr->name->name);
        static::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        static::assertSame('this', $result->expr->var->name);
        static::assertCount(1, $result->expr->args);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        static::assertSame(1, $result->expr->args[0]->value->value);
    }

    /**
     * @return void
     */
    public function testParsesStaticMethodCall(): void
    {
        $source = <<<'SOURCE'
<?php

self::call(1, 2
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\StaticCall::class, $result->expr);
        static::assertSame('call', $result->expr->name->name);
        static::assertInstanceOf(Node\Name::class, $result->expr->class);
        static::assertSame('self', $result->expr->class->toString());
        static::assertCount(2, $result->expr->args);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        static::assertSame(1, $result->expr->args[0]->value->value);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[1]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[1]->value);
        static::assertSame(2, $result->expr->args[1]->value->value);
    }

    /**
     * @return void
     */
    public function testParsesStaticMethodCallWithMissingArgument(): void
    {
        $source = <<<'SOURCE'
<?php

self::call(1,
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\StaticCall::class, $result->expr);
        static::assertSame('call', $result->expr->name->name);
        static::assertInstanceOf(Node\Name::class, $result->expr->class);
        static::assertSame('self', $result->expr->class->toString());
        static::assertCount(1, $result->expr->args);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        static::assertSame(1, $result->expr->args[0]->value->value);
    }

    /**
     * @return void
     */
    public function testParsesConstructorCall(): void
    {
        $source = <<<'SOURCE'
<?php

new Foo(1, 2
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\New_::class, $result->expr);
        static::assertInstanceOf(Node\Name::class, $result->expr->class);
        static::assertSame('Foo', $result->expr->class->toString());
        static::assertCount(2, $result->expr->args);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        static::assertSame(1, $result->expr->args[0]->value->value);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[1]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[1]->value);
        static::assertSame(2, $result->expr->args[1]->value->value);
    }

    /**
     * @return void
     */
    public function testParsesConstructorCallWithMissingArgument(): void
    {
        $source = <<<'SOURCE'
<?php

new Foo(1,
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        static::assertSame(1, count($result));

        $result = array_shift($result);

        static::assertInstanceOf(Node\Stmt\Expression::class, $result);
        static::assertInstanceOf(Node\Expr\New_::class, $result->expr);
        static::assertInstanceOf(Node\Name::class, $result->expr->class);
        static::assertSame('Foo', $result->expr->class->toString());
        static::assertCount(1, $result->expr->args);
        static::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        static::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        static::assertSame(1, $result->expr->args[0]->value->value);
    }
}
