<?php

namespace PhpIntegrator\Analysis;

use LogicException;
use UnexpectedValueException;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpParser\Node;

/**
 * Fetches method information from a {@see Node\Expr\MethodCall} node.
 */
class MethodCallMethodInfoRetriever
{
    /**
     * @var NodeTypeDeducerInterface
     */
    protected $nodeTypeDeducer;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @param NodeTypeDeducerInterface $nodeTypeDeducer
     * @param ClasslikeInfoBuilder     $classlikeInfoBuilder
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilder $classlikeInfoBuilder
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param Node\Expr\MethodCall|Node\Expr\StaticCall $node
     * @param string|null                               $file
     * @param string                                    $code
     * @param int                                       $offset
     *
     * @throws UnexpectedValueException when a dynamic method call is passed.
     * @throws UnexpectedValueException when the type method is called on could not be determined.
     *
     * @return array[]
     */
    public function retrieve(Node\Expr $node, ?string $file, string $code, int $offset): array
    {
        if ($node->name instanceof Node\Expr) {
            // Can't currently deduce type of an expression such as "$this->{$foo}()";
            throw new UnexpectedValueException('Can\'t determine information of dynamic method call');
        } elseif (!$node instanceof Node\Expr\MethodCall && !$node instanceof Node\Expr\StaticCall) {
            throw new LogicException('Expected method call node, got ' . get_class($node) . ' instead');
        }

        $objectNode = ($node instanceof Node\Expr\MethodCall) ? $node->var : $node->class;

        $typesOfVar = $this->nodeTypeDeducer->deduce($objectNode, $file, $code, $offset);

        $infoElements = [];

        foreach ($typesOfVar as $type) {
            $info = null;

            try {
                $info = $this->classlikeInfoBuilder->getClasslikeInfo($type);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            if (!isset($info['methods'][$node->name])) {
                continue;
            }

            $infoElements[] = $info['methods'][$node->name];
        }

        return $infoElements;
    }
}
