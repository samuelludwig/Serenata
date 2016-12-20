<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\ClassConstFetch} node.
 */
class ClassConstFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerFactoryInterface
     */
    protected $nodeTypeDeducerFactory;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @param NodeTypeDeducerFactoryInterface $nodeTypeDeducerFactory
     * @param ClasslikeInfoBuilder            $classlikeInfoBuilder
     */
    public function __construct(
        NodeTypeDeducerFactoryInterface $nodeTypeDeducerFactory,
        ClasslikeInfoBuilder $classlikeInfoBuilder
    ) {
        $this->nodeTypeDeducerFactory = $nodeTypeDeducerFactory;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @inheritDoc
     */
    public function deduce(Node $node, $file, $code, $offset)
    {
        if (!$node instanceof Node\Expr\ClassConstFetch) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromClassConstFetchNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param string|null               $file
     * @param string                    $code
     * @param int                       $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromClassConstFetchNode(Node\Expr\ClassConstFetch $node, $file, $code, $offset)
    {
        $typesOfVar = [];

        try {
            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($node->class);

            $typesOfVar = $nodeTypeDeducer->deduce($node->class, $file, $code, $offset);
        } catch (UnexpectedValueException $e) {
            return [];
        }

        $types = [];

        foreach ($typesOfVar as $type) {
            $info = null;

            try {
                $info = $this->classlikeInfoBuilder->getClasslikeInfo($type);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            if (isset($info['constants'][$node->name])) {
                $fetchedTypes = $this->fetchResolvedTypesFromTypeArrays($info['constants'][$node->name]['types']);

                if (!empty($fetchedTypes)) {
                    $types += array_combine($fetchedTypes, array_fill(0, count($fetchedTypes), true));
                }
            }
        }

        // We use an associative array so we automatically avoid duplicate types.
        return array_keys($types);
    }
}
