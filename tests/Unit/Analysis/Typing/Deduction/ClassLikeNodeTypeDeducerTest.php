<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Typing\Deduction;

use PhpIntegrator\Analysis\Typing\Deduction\ClassLikeNodeTypeDeducer;

use PhpParser\Node;

class ClassLikeNodeTypeDeducerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ClassLikeNodeTypeDeducer
     */
    private $classLikeNodeTypeDeducer;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->classLikeNodeTypeDeducer = new ClassLikeNodeTypeDeducer();
    }

    /**
     * @return void
     */
    public function testClassNode(): void
    {
        $node = new Node\Stmt\Class_('A');

        $this->assertEquals(['A'], $this->classLikeNodeTypeDeducer->deduce($node, '', '', 0));
    }

    /**
     * @return void
     */
    public function testInterfaceNode(): void
    {
        $node = new Node\Stmt\Interface_('A');

        $this->assertEquals(['A'], $this->classLikeNodeTypeDeducer->deduce($node, '', '', 0));
    }

    /**
     * @return void
     */
    public function testTraitNode(): void
    {
        $node = new Node\Stmt\Trait_('A');

        $this->assertEquals(['A'], $this->classLikeNodeTypeDeducer->deduce($node, '', '', 0));
    }
}
