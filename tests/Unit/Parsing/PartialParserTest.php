<?php

namespace Serenata\Tests\Unit\Parsing;

use Serenata\Parsing\PartialParser;

use Serenata\Parsing\Node\Expr;

use PhpParser\Node;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class PartialParserTest extends TestCase
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ConstFetch::class, $result->expr);
        self::assertSame('array_walk', $result->expr->name->toString());
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        self::assertSame('Bar', $result->expr->class->toString());
        self::assertSame('TEST_CONSTANT', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\StaticCall::class, $result->expr);
        self::assertSame('NamespaceTest\Bar', $result->expr->class->toString());
        self::assertSame('staticmethod', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertSame('this', $result->expr->var->name);
        self::assertSame('someProperty', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\StaticPropertyFetch::class, $result->expr);
        self::assertSame('self', $result->expr->class->toString());
        self::assertSame('someProperty', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        self::assertSame('.:', $result->expr->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var->var);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var->name);
        self::assertSame('this', $result->expr->var->var->name);
        self::assertSame('foo', $result->expr->var->name->name);
        self::assertSame('test', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertSame('this', $result->expr->var->name);
        self::assertSame('', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertInstanceOf(Node\Expr\New_::class, $result->expr->var);
        self::assertSame('Foo\Bar', $result->expr->var->class->toString());
        self::assertSame('doFoo', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var->var);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var->var->var);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->var->var->var->var);
        self::assertSame('testChaining', $result->expr->var->name->name);
        self::assertSame('testChaining', $result->expr->var->var->name->name);
        self::assertSame('testChaining', $result->expr->var->var->var->name->name);
        self::assertSame('testChaining', $result->expr->var->var->var->var->name->name);
        self::assertSame('testChai', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        self::assertSame('static', $result->expr->class->toString());
        self::assertSame('doSome', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

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
    public function testParsesEncapsedStringWithIntepolatedMethodCall(): void
    {
        $source = <<<'SOURCE'
<?php

"{$test->foo()}"
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->parts[0]);
        self::assertSame('test', $result->expr->parts[0]->var->name);
        self::assertSame('foo', $result->expr->parts[0]->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr->parts[0]);
        self::assertSame('test', $result->expr->parts[0]->var->name);
        self::assertSame('foo', $result->expr->parts[0]->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        self::assertSame('{$a->asd()[0]}', $result->expr->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        self::assertSame('TEST', $result->expr->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\String_::class, $result->expr);
        self::assertSame('TEST', $result->expr->value);
    }

    /**
     * @return void
     */
    public function testParsesHeredocContainingInterpolatedValues(): void
    {
        $source = <<<'SOURCE'
<?php

<<<BLOCK
EOF: {$foo[2]->bar()} some_text

This is / some text.

BLOCK
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Scalar\Encapsed::class, $result->expr);
        self::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[0]);
        self::assertSame('EOF: ', $result->expr->parts[0]->value);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr->parts[1]);
        self::assertInstanceOf(Node\Expr\ArrayDimFetch::class, $result->expr->parts[1]->var);
        self::assertSame('foo', $result->expr->parts[1]->var->var->name);
        self::assertSame(2, $result->expr->parts[1]->var->dim->value);
        self::assertSame('bar', $result->expr->parts[1]->name->name);
        self::assertInstanceOf(Node\Scalar\EncapsedStringPart::class, $result->expr->parts[2]);
        self::assertSame(" some_text\n\nThis is / some text.\n", $result->expr->parts[2]->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ClassConstFetch::class, $result->expr);
        self::assertSame('Test', $result->expr->class->toString());
        self::assertSame('class', $result->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\BinaryOp\ShiftLeft::class, $result->expr);
        self::assertSame(1, $result->expr->left->value);
        self::assertSame(0, $result->expr->right->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\BooleanNot::class, $result->expr);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr->expr);
        self::assertSame('this', $result->expr->expr->var->name);
        self::assertSame('one', $result->expr->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\ErrorSuppress::class, $result->expr);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr->expr);
        self::assertSame('this', $result->expr->expr->var->name);
        self::assertSame('one', $result->expr->expr->name->name);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\Ternary::class, $result->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->cond);
        self::assertSame('test', $result->expr->cond->name);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->if);
        self::assertSame('a', $result->expr->if->name);
        self::assertInstanceOf(Expr\Dummy::class, $result->expr->else);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\Assign::class, $result->expr);
        self::assertSame('b', $result->expr->var->name);
        self::assertInstanceOf(Node\Expr\Ternary::class, $result->expr->expr);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->expr->cond);
        self::assertSame('test', $result->expr->expr->cond->name);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->expr->if);
        self::assertSame('a', $result->expr->expr->if->name);
        self::assertInstanceOf(Expr\Dummy::class, $result->expr->expr->else);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\FuncCall::class, $result->expr);
        self::assertSame('call', $result->expr->name->toString());
        self::assertCount(2, $result->expr->args);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        self::assertSame(1, $result->expr->args[0]->value->value);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[1]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[1]->value);
        self::assertSame(2, $result->expr->args[1]->value->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\FuncCall::class, $result->expr);
        self::assertSame('call', $result->expr->name->toString());
        self::assertCount(1, $result->expr->args);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        self::assertSame(1, $result->expr->args[0]->value->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertSame('call', $result->expr->name->name);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertCount(2, $result->expr->args);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        self::assertSame(1, $result->expr->args[0]->value->value);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[1]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[1]->value);
        self::assertSame(2, $result->expr->args[1]->value->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\MethodCall::class, $result->expr);
        self::assertSame('call', $result->expr->name->name);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var);
        self::assertSame('this', $result->expr->var->name);
        self::assertCount(1, $result->expr->args);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        self::assertSame(1, $result->expr->args[0]->value->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\StaticCall::class, $result->expr);
        self::assertSame('call', $result->expr->name->name);
        self::assertInstanceOf(Node\Name::class, $result->expr->class);
        self::assertSame('self', $result->expr->class->toString());
        self::assertCount(2, $result->expr->args);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        self::assertSame(1, $result->expr->args[0]->value->value);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[1]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[1]->value);
        self::assertSame(2, $result->expr->args[1]->value->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\StaticCall::class, $result->expr);
        self::assertSame('call', $result->expr->name->name);
        self::assertInstanceOf(Node\Name::class, $result->expr->class);
        self::assertSame('self', $result->expr->class->toString());
        self::assertCount(1, $result->expr->args);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        self::assertSame(1, $result->expr->args[0]->value->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\New_::class, $result->expr);
        self::assertInstanceOf(Node\Name::class, $result->expr->class);
        self::assertSame('Foo', $result->expr->class->toString());
        self::assertCount(2, $result->expr->args);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        self::assertSame(1, $result->expr->args[0]->value->value);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[1]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[1]->value);
        self::assertSame(2, $result->expr->args[1]->value->value);
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

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\New_::class, $result->expr);
        self::assertInstanceOf(Node\Name::class, $result->expr->class);
        self::assertSame('Foo', $result->expr->class->toString());
        self::assertCount(1, $result->expr->args);
        self::assertInstanceOf(Node\Arg::class, $result->expr->args[0]);
        self::assertInstanceOf(Node\Scalar\LNumber::class, $result->expr->args[0]->value);
        self::assertSame(1, $result->expr->args[0]->value->value);
    }

    /**
     * @return void
     */
    public function testParsesDoubleObjectArrow(): void
    {
        $source = <<<'SOURCE'
<?php

$test->->
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        $result = array_shift($result);

        self::assertInstanceOf(Node\Stmt\Expression::class, $result);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr);
        self::assertInstanceOf(Node\Expr\PropertyFetch::class, $result->expr->var);
        self::assertInstanceOf(Node\Expr\Variable::class, $result->expr->var->var);
        self::assertSame('test', $result->expr->var->var->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->var->name);
        self::assertSame('', $result->expr->var->name->name);
        self::assertInstanceOf(Node\Identifier::class, $result->expr->name);
        self::assertSame('', $result->expr->name->name);
    }

    /**
     * @return void
     */
    public function testParsesMultipleObjectArrows(): void
    {
        $source = <<<'SOURCE'
<?php

$test->->->->
SOURCE;

        $result = $this->createPartialParser()->parse($source);

        self::assertNotNull($result);
        self::assertSame(1, count($result));

        // Mostly just to test that this works as an extension of testParsesDoubleObjectArrows, no need to specify
        // all data here.
    }
}
