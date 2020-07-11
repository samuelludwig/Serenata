<?php

namespace Serenata\Tests\Unit\Analysis\Visiting;

use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

use PHPUnit\Framework\TestCase;

use Serenata\Analysis\Visiting\ScopeLimitingVisitor;

use Serenata\Common\Position;

use Serenata\Utility\TextDocumentItem;

final class ScopeLimitingVisitorTest extends TestCase
{
    /**
     * @return void
     */
    public function testThatTheVisitingOperationIsNonDestructiveAndNodesAreNotPermanentlyModifiedButOnlyDuringTraversal(): void
    {
        $code = <<<'SOURCE'
            <?php

            namespace A;

            class Foo
            {
                protected $prop;

                public function someMethod()
                {
                    if ($a instanceof \Traversable) {

                    } elseif ($b instanceof \DateTime) {
                        $b->format();
                    } elseif ($c instanceof self) {
                        $d = $c->prop;
                    }
                }
            }
SOURCE;

        $code = trim($code);

        $lexer = new Lexer([
            'usedAttributes' => [
                'comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos',
            ],
        ]);

        $parserFactory = new ParserFactory();

        $parser = $parserFactory->create(ParserFactory::PREFER_PHP7, $lexer, []);

        $nodes = $parser->parse($code);

        $stateBefore = serialize($nodes);

        $visitor = new ScopeLimitingVisitor(
            new TextDocumentItem('path', $code),
            new Position(15, 12)
        );

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        $stateAfter = serialize($nodes);

        self::assertTrue(
            $stateBefore === $stateAfter,
            'Using a ScopeLimitingVisitor is destructive. If it alters the state of the nodes, it must also restore them on exit.'
        );
    }
}
