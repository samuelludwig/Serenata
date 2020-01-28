<?php

namespace Serenata\Analysis\Visiting;

use PhpParser\Node;

use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that attaches byte offsets of {@see FunctionLike} body (i.e. the curly braces) start and end positions.
 *
 * Using tokens instead of just code parsing will ensure that comments that contain these symbols become token arrays
 * with e.g. T_COMMENT as type, so we safely skip them this way instead of mistaking them for actual code.
 */
final class FunctionLikeBodyOffsetAttachingVisitor extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    private $tokens;

    /**
     * @param array $tokens
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if ($node instanceof Node\FunctionLike) {
            if ($node->getStmts() === null) {
                return null; // Skip methods without a body (e.g. interface or abstract ones).
            }

            $node->setAttribute('bodyStartFilePos', $this->locateBodyStartByteOffset($node));
            $node->setAttribute('bodyEndFilePos', $this->locateBodyEndByteOffset($node));
        }

        return null;
    }

    /**
     * @param Node\FunctionLike $node
     *
     * @return int|null
     */
    private function locateBodyStartByteOffset(Node\FunctionLike $node): ?int
    {
        $byteOffset = $node->getAttribute('startFilePos');

        for ($i = $node->getAttribute('startTokenPos'); $i < $node->getAttribute('endTokenPos'); ++$i) {
            if ($node instanceof Node\Expr\ArrowFunction && $this->tokens[$i][0] === T_DOUBLE_ARROW) {
                return $byteOffset + 1;
            } elseif ($this->tokens[$i] === '{') {
                return $byteOffset;
            }

            $byteOffset += strlen(is_array($this->tokens[$i]) ? $this->tokens[$i][1] : $this->tokens[$i]);
        }

        // Can actually happen in erroneous code where the body isn't written yet in real-world scenarios.
        return null;
    }

    /**
     * @param Node\FunctionLike $node
     *
     * @return int|null
     */
    private function locateBodyEndByteOffset(Node\FunctionLike $node): ?int
    {
        $byteOffset = $node->getAttribute('endFilePos');

        if ($node instanceof Node\Expr\ArrowFunction) {
            return $byteOffset;
        }

        for ($i = $node->getAttribute('endTokenPos'); $i > 0; --$i) {
            if ($this->tokens[$i] === '}') {
                return $byteOffset;
            }

            $byteOffset -= strlen(is_array($this->tokens[$i]) ? $this->tokens[$i][1] : $this->tokens[$i]);
        }

        // Can actually happen in erroneous code where the body isn't written yet in real-world scenarios.
        return null;
    }
}
