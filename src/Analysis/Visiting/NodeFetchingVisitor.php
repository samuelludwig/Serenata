<?php

namespace Serenata\Analysis\Visiting;

use PhpParser\Node;
use PhpParser\Comment;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

use Serenata\Common\Position;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Visitor that retrieves the node at a specific location.
 */
final class NodeFetchingVisitor extends NodeVisitorAbstract
{
    /**
     * @var int
     */
    private $byteOffset;

    /**
     * @var Node|null
     */
    private $matchingNode;

    /**
     * @var Node|null
     */
    private $mostInterestingNode;

    /**
     * @var Comment|null
     */
    private $comment;

    /**
     * @param TextDocumentItem $textDocument
     * @param Position          $position
     */
    public function __construct(TextDocumentItem $textDocument, Position $position)
    {
        $this->byteOffset = $position->getAsByteOffsetInString($textDocument->getText(), PositionEncoding::VALUE);
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        $this->analyzeNodeComments($node);

        $endFilePos = $node->getAttribute('endFilePos');
        $startFilePos = $node->getAttribute('startFilePos');

        if ($node instanceof Node\Expr\Error && $endFilePos < $startFilePos) {
            // As php-parser uses inclusive ranges, a range where startFilePos === $endFilePos would still describe a
            // single character, hence the end can lie before the start. This works around that to ensure we can still
            // fetch the error node itself rather than null.
            //
            // See also https://github.com/nikic/PHP-Parser/issues/440
            $endFilePos = $startFilePos;
        }

        if ($endFilePos < $this->byteOffset) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($startFilePos > $this->byteOffset) {
            return NodeTraverser::STOP_TRAVERSAL;
        }

        $this->matchingNode = $node;

        if (!$node instanceof Node\Name && !$node instanceof Node\Identifier) {
            $this->mostInterestingNode = $node;
        }
    }

    /**
     * @param Node $node
     */
    private function analyzeNodeComments(Node $node): void
    {
        foreach ($node->getComments() as $comment) {
            $this->analyzeNodeComment($comment);
        }
    }

    /**
     * @param Comment $comment
     */
    private function analyzeNodeComment(Comment $comment): void
    {
        // NOTE: This is now an open (exclusive) range.
        $endPosition = $comment->getFilePos() + strlen($comment->getText());

        if ($this->byteOffset >= $comment->getFilePos() && $this->byteOffset < $endPosition) {
            $this->comment = $comment;
        }
    }

    /**
     * @return Node|null
     */
    public function getNode(): ?Node
    {
        return $this->matchingNode;
    }

    /**
     * Returns the same as {@see getNode}, or the nearest node that is more interesting.
     *
     * "More interesting" is defined in terms of what is more useful. {@see getNode} may return the name node inside a
     * function call, whilst this method will return the function call instead.
     *
     * @return Node|null
     */
    public function getNearestInterestingNode(): ?Node
    {
        return $this->mostInterestingNode;
    }

    /**
     * @return Comment|null
     */
    public function getComment(): ?Comment
    {
        return $this->comment;
    }
}
