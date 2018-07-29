<?php

namespace Serenata\Analysis\Visiting;

use LogicException;

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
                return; // Skip methods without a body (e.g. interface or abstract ones).
            }

            $node->setAttribute('bodyStartFilePos', $this->locateBodyStartByteOffset($node));
            $node->setAttribute('bodyEndFilePos', $this->locateBodyEndByteOffset($node));
        }
    }

    /**
     * @param Node\FunctionLike $node
     *
     * @return int
     */
    private function locateBodyStartByteOffset(Node\FunctionLike $node): int
    {
        $byteOffset = $node->getAttribute('startFilePos');

        for ($i = $node->getAttribute('startTokenPos'); $i < $node->getAttribute('endTokenPos'); ++$i) {
            if ($this->tokens[$i] === '{') {
                return $byteOffset;
            }

            $byteOffset += strlen(is_array($this->tokens[$i]) ? $this->tokens[$i][1] : $this->tokens[$i]);
        }

        throw new LogicException('Could not detect body start for node of type "' . get_class($node) . '"');
    }

    /**
     * @param Node\FunctionLike $node
     *
     * @return int
     */
    private function locateBodyEndByteOffset(Node\FunctionLike $node): int
    {
        $byteOffset = $node->getAttribute('endFilePos');

        for ($i = $node->getAttribute('endTokenPos'); $i > 0; --$i) {
            if ($this->tokens[$i] === '}') {
                return $byteOffset;
            }

            $byteOffset -= strlen(is_array($this->tokens[$i]) ? $this->tokens[$i][1] : $this->tokens[$i]);
        }

        throw new LogicException('Could not detect body end for node of type "' . get_class($node) . '"');
    }
}
