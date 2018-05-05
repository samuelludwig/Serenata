<?php

namespace Serenata\Tests\Unit\Analysis;

use Serenata\Analysis\Node\ConstNameNodeFqsenDeterminer;

use Serenata\NameQualificationUtilities\ConstantPresenceIndicatorInterface;

use PhpParser\Node;

class ConstNameNodeFqsenDeterminerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testFullyQualifiedName(): void
    {
        $existenceChecker = $this->getMockBuilder(ConstantPresenceIndicatorInterface::class)
            ->setMethods(['isPresent'])
            ->getMock();

        $existenceChecker->method('isPresent')->will($this->returnValue(false));

        $determiner = new ConstNameNodeFqsenDeterminer($existenceChecker);

        $node = new Node\Name\FullyQualified('\A\B');
        $node->setAttribute('namespace', null);

        static::assertSame('\A\B', $determiner->determine($node));
    }

    /**
     * @return void
     */
    public function testQualifiedName(): void
    {
        $existenceChecker = $this->getMockBuilder(ConstantPresenceIndicatorInterface::class)
            ->setMethods(['isPresent'])
            ->getMock();

        $existenceChecker->method('isPresent')->will($this->returnValue(false));

        $determiner = new ConstNameNodeFqsenDeterminer($existenceChecker);

        $namespaceNode = new Node\Name('N');

        $node = new Node\Name('A\B');
        $node->setAttribute('namespace', $namespaceNode);

        static::assertSame('\N\A\B', $determiner->determine($node));
    }

    /**
     * @return void
     */
    public function testUnqualifiedNameThatDoesNotExistRelativeToCurrentNamespace(): void
    {
        $existenceChecker = $this->getMockBuilder(ConstantPresenceIndicatorInterface::class)
            ->setMethods(['isPresent'])
            ->getMock();

        $existenceChecker->method('isPresent')->will($this->returnValue(false));

        $determiner = new ConstNameNodeFqsenDeterminer($existenceChecker);

        $namespaceNode = new Node\Name('N');

        $node = new Node\Name('A');
        $node->setAttribute('namespace', $namespaceNode);

        static::assertSame('\A', $determiner->determine($node));
    }

    /**
     * @return void
     */
    public function testUnqualifiedNameThatExistsRelativeToCurrentNamespace(): void
    {
        $existenceChecker = $this->getMockBuilder(ConstantPresenceIndicatorInterface::class)
            ->setMethods(['isPresent'])
            ->getMock();

        $existenceChecker->method('isPresent')->will($this->returnValue(true));

        $determiner = new ConstNameNodeFqsenDeterminer($existenceChecker);

        $namespaceNode = new Node\Name('N');

        $node = new Node\Name('A');
        $node->setAttribute('namespace', $namespaceNode);

        static::assertSame('\N\A', $determiner->determine($node));
    }
}
