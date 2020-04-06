<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use PhpParser\Node;

use Serenata\Parsing\InvalidTypeNode;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\Variable} node.
 */
final class VariableNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var LocalTypeScanner
     */
    private $localTypeScanner;

    /**
     * @param LocalTypeScanner $localTypeScanner
     */
    public function __construct(LocalTypeScanner $localTypeScanner)
    {
        $this->localTypeScanner = $localTypeScanner;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Expr\Variable) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        } elseif ($context->getNode()->name instanceof Node\Expr) {
            return new InvalidTypeNode(); // Can't currently deduce type of a variable such as "$$this".
        }

        return $this->localTypeScanner->getLocalExpressionTypes(
            $context->getTextDocumentItem(),
            $context->getPosition(),
            '$' . $context->getNode()->name
        );
    }
}
