<?php

namespace Serenata\Tests\Unit\Analysis;

use Serenata\Analysis\Node\FunctionNameNodeFqsenDeterminer;

use Serenata\NameQualificationUtilities\FunctionPresenceIndicatorInterface;

use PhpParser\Node;

class FunctionNameNodeFqsenDeterminerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testFullyQualifiedName(): void
    {
        $existenceChecker = $this->getMockBuilder(FunctionPresenceIndicatorInterface::class)
            ->setMethods(['isPresent'])
            ->getMock();

        $existenceChecker->method('isPresent')->will($this->returnValue(false));

        $determiner = new FunctionNameNodeFqsenDeterminer($existenceChecker);

        $node = new Node\Name\FullyQualified('\A\foo');
        $node->setAttribute('namespace', null);

        static::assertSame('\A\foo', $determiner->determine($node));
    }

    /**
     * @return void
     */
    public function testQualifiedName(): void
    {
        $existenceChecker = $this->getMockBuilder(FunctionPresenceIndicatorInterface::class)
            ->setMethods(['isPresent'])
            ->getMock();

        $existenceChecker->method('isPresent')->will($this->returnValue(false));

        $determiner = new FunctionNameNodeFqsenDeterminer($existenceChecker);

        $namespaceNode = new Node\Name('N');

        $node = new Node\Name('A\foo');
        $node->setAttribute('namespace', $namespaceNode);

        static::assertSame('\N\A\foo', $determiner->determine($node));
    }

    /**
     * @return void
     */
    public function testUnqualifiedNameThatDoesNotExistRelativeToCurrentNamespace(): void
    {
        $existenceChecker = $this->getMockBuilder(FunctionPresenceIndicatorInterface::class)
            ->setMethods(['isPresent'])
            ->getMock();

        $existenceChecker->method('isPresent')->will($this->returnValue(false));

        $determiner = new FunctionNameNodeFqsenDeterminer($existenceChecker);

        $namespaceNode = new Node\Name('N');

        $node = new Node\Name('foo');
        $node->setAttribute('namespace', $namespaceNode);

        static::assertSame('\foo', $determiner->determine($node));
    }

    /**
     * @return void
     */
    public function testUnqualifiedNameThatExistsRelativeToCurrentNamespace(): void
    {
        $existenceChecker = $this->getMockBuilder(FunctionPresenceIndicatorInterface::class)
            ->setMethods(['isPresent'])
            ->getMock();

        $existenceChecker->method('isPresent')->will($this->returnValue(true));

        $determiner = new FunctionNameNodeFqsenDeterminer($existenceChecker);

        $namespaceNode = new Node\Name('N');

        $node = new Node\Name('foo');
        $node->setAttribute('namespace', $namespaceNode);

        static::assertSame('\N\foo', $determiner->determine($node));
    }
}
