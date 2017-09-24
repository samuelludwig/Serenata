<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Typing\Deduction;

use DateTime;

use PhpIntegrator\Analysis\Typing\Deduction\ClassLikeNodeTypeDeducer;

use PhpIntegrator\Indexing\Structures;

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

        $file = new Structures\File('', new DateTime(), []);

        static::assertSame(['A'], $this->classLikeNodeTypeDeducer->deduce($node, $file, '', 0));
    }

    /**
     * @return void
     */
    public function testInterfaceNode(): void
    {
        $node = new Node\Stmt\Interface_('A');

        $file = new Structures\File('', new DateTime(), []);

        static::assertSame(['A'], $this->classLikeNodeTypeDeducer->deduce($node, $file, '', 0));
    }

    /**
     * @return void
     */
    public function testTraitNode(): void
    {
        $node = new Node\Stmt\Trait_('A');

        $file = new Structures\File('', new DateTime(), []);

        static::assertSame(['A'], $this->classLikeNodeTypeDeducer->deduce($node, $file, '', 0));
    }

    /**
     * @return void
     */
    public function testAnonymousClassNode(): void
    {
        $node = new Node\Stmt\Class_(null, [], [
            'startFilePos' => 9
        ]);

        $file = new Structures\File('/test/path', new DateTime(), []);

        static::assertSame(
            ['\(anonymous_a19f6c462322bef8d3cad086eca0e32a_9)'],
            $this->classLikeNodeTypeDeducer->deduce($node, $file, '', 0)
        );
    }
}
