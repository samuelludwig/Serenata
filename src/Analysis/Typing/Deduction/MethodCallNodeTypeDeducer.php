<?php

namespace Serenata\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpParser\Node;

use Serenata\Analysis\Node\MethodCallMethodInfoRetriever;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\MethodCall} or a {@see Node\Expr\StaticCall} node.
 */
final class MethodCallNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var MethodCallMethodInfoRetriever
     */
    private $methodCallMethodInfoRetriever;

    /**
     * @param MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever
     */
    public function __construct(MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever)
    {
        $this->methodCallMethodInfoRetriever = $methodCallMethodInfoRetriever;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Expr\MethodCall && !$context->getNode() instanceof Node\Expr\StaticCall) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $infoItems = null;

        try {
            $infoItems = $this->methodCallMethodInfoRetriever->retrieve(
                $context->getNode(),
                $context->getTextDocumentItem(),
                $context->getPosition()
            );
        } catch (UnexpectedValueException $e) {
            return [];
        }

        $types = [];

        foreach ($infoItems as $info) {
            $fetchedTypes = $this->fetchResolvedTypesFromTypeArrays($info['returnTypes']);

            if (count($fetchedTypes) > 0) {
                $types += array_combine($fetchedTypes, array_fill(0, count($fetchedTypes), true));
            }
        }

        // Use associative array to avoid duplicate types.
        return array_keys($types);
    }
}
