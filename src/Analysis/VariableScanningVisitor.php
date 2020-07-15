<?php

namespace Serenata\Analysis;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

use Serenata\Common\Position;

use Serenata\Utility\NodeHelpers;
use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Visitor that queries the nodes for information about available (set) variables.
 */
final class VariableScanningVisitor extends NodeVisitorAbstract
{
    /**
     * @var string[]
     */
    private $variables = [];

    /**
     * @var int
     */
    private $byteOffset;

    /**
     * @var bool
     */
    private $hasThisContext;

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
        // NOTE: Position ranges are closed (inclusive).
        if ($node->getAttribute('startFilePos') >= $this->byteOffset) {
            // We've gone beyond the requested position, there is nothing here that can still be relevant anymore.
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node->getAttribute('startFilePos') <= $this->byteOffset &&
            $node->getAttribute('endFilePos') >= $this->byteOffset
        ) {
            if ($node instanceof Node\Stmt\ClassLike) {
                $this->hasThisContext = true;

                // We've entered a new scope, variables that we previously picked up are outside of it and not available
                // here.
                $this->variables = [];
            } elseif ($node instanceof Node\FunctionLike) {
                $isPositionInsideClosureUses = false;

                if ($node instanceof Node\Expr\Closure) {
                    $isPositionInsideClosureUses = $this->isCurrentOffsetInsideClosureUses($node);

                    if (!$isPositionInsideClosureUses) {
                        // Closures can have a custom object bound to the $this variable. There is no way for us to detect
                        // whether this actually happened (as that is only known at runtime), so just include the variable.
                        $this->hasThisContext = true;
                    }
                }

                if (!$isPositionInsideClosureUses) {
                    $this->variables = [];
                }
            }
        }

        if ($node instanceof Node\Expr\Variable) {
            /** @var Node\Expr\Assign|null $parentAssignmentExpression */
            $parentAssignmentExpression = NodeHelpers::findAncestorOfAnyType($node, Node\Expr\Assign::class);

            if (($node->getAttribute('endFilePos') + 1) < $this->byteOffset && (
                $parentAssignmentExpression === null ||
                ($parentAssignmentExpression->getAttribute('endFilePos') + 1) < $this->byteOffset ||
                (
                    $parentAssignmentExpression->expr instanceof Node\Expr\Closure &&
                    $this->isCurrentOffsetInsideClosureUses($parentAssignmentExpression->expr)
                )
            )) {
                $this->parseVariable($node);
            }

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof Node\Expr\ClosureUse) {
            if (!$this->isCurrentOffsetInsideClosureUses($node->getAttribute('parent'))) {
                $this->parseClosureUse($node);
            }

            // Otherwise the above variable instance check will pick this up again.
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof Node\Param) {
            if (!$node->getAttribute('parent') instanceof Node\Expr\Closure ||
                !$this->isCurrentOffsetInsideClosureUses($node->getAttribute('parent'))
            ) {
                $this->parseParam($node);
            }

            // Otherwise the above variable instance check will pick this up again.
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @param Node\Expr\Closure $node
     *
     * @return bool
     */
    private function isCurrentOffsetInsideClosureUses(Node\Expr\Closure $node): bool
    {
        foreach ($node->uses as $use) {
            if ($use->getAttribute('startFilePos') <= $this->byteOffset &&
                ($use->getAttribute('endFilePos') + 1) >= $this->byteOffset
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Node\Expr\Variable $node
     *
     * @return void
     */
    private function parseVariable(Node\Expr\Variable $node): void
    {
        if (is_string($node->name)) {
            $this->variables[] = '$' . $node->name;
        }
    }

    /**
     * @param Node\Expr\ClosureUse $node
     *
     * @return void
     */
    private function parseClosureUse(Node\Expr\ClosureUse $node): void
    {
        if (!is_string($node->var->name)) {
            return;
        }

        $this->variables[] = '$' . $node->var->name;
    }

    /**
     * @param Node\Param $node
     *
     * @return void
     */
    private function parseParam(Node\Param $node): void
    {
        if (!$node->var instanceof Node\Expr\Variable) {
            return;
        } elseif (!is_string($node->var->name)) {
            return;
        }

        $this->variables[] = '$' . $node->var->name;
    }

    /**
     * Retrieves the detected variables.
     *
     * @return string[]
     */
    public function getVariables(): array
    {
        $variables = $this->variables;

        if ($this->hasThisContext) {
            $variables[] = '$this';
        }

        return $variables;
    }

    /**
     * Retrieves the detected variables, sorted by their proximity to the configured location. Note that $this will
     * still be listed first as it's always closest in the sense that it's always available.
     *
     * @return string[]
     */
    public function getVariablesSortedByProximity(): array
    {
        $variables = array_reverse($this->variables);

        if ($this->hasThisContext) {
            array_unshift($variables, '$this');
        }

        return $variables;
    }
}
