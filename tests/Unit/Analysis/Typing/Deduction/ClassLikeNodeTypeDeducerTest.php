<?php

namespace Serenata\Tests\Unit\Analysis\Typing\Deduction;

use DateTime;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\ClassLikeNodeTypeDeducer;

use Serenata\Indexing\Structures;

use PhpParser\Node;

use Serenata\Utility\TextDocumentItem;
use PHPUnit\Framework\TestCase;

final class ClassLikeNodeTypeDeducerTest extends TestCase
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

        static::assertEquals(new IdentifierTypeNode('A'), $this->classLikeNodeTypeDeducer->deduce(
            new TypeDeductionContext($node, new TextDocumentItem('', ''))
        ));
    }

    /**
     * @return void
     */
    public function testInterfaceNode(): void
    {
        $node = new Node\Stmt\Interface_('A');

        $file = new Structures\File('', new DateTime(), []);

        static::assertEquals(new IdentifierTypeNode('A'), $this->classLikeNodeTypeDeducer->deduce(
            new TypeDeductionContext($node, new TextDocumentItem('', ''))
        ));
    }

    /**
     * @return void
     */
    public function testTraitNode(): void
    {
        $node = new Node\Stmt\Trait_('A');

        $file = new Structures\File('', new DateTime(), []);

        static::assertEquals(new IdentifierTypeNode('A'), $this->classLikeNodeTypeDeducer->deduce(
            new TypeDeductionContext($node, new TextDocumentItem('', ''))
        ));
    }

    /**
     * @return void
     */
    public function testAnonymousClassNode(): void
    {
        $node = new Node\Stmt\Class_(null, [], [
            'startFilePos' => 9,
        ]);

        $file = new Structures\File('file:////test/path', new DateTime(), []);

        static::assertEquals(
            new IdentifierTypeNode('\anonymous_d41d8cd98f00b204e9800998ecf8427e_9'),
            $this->classLikeNodeTypeDeducer->deduce(new TypeDeductionContext($node, new TextDocumentItem('', '')))
        );
    }
}
