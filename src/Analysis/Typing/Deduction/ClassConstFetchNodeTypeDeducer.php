<?php

namespace Serenata\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\ClassConstFetch} node.
 */
final class ClassConstFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @param NodeTypeDeducerInterface      $nodeTypeDeducer
     * @param ClasslikeInfoBuilderInterface $classlikeInfoBuilder
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Expr\ClassConstFetch) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $typesOfVar = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->class,
            $context->getTextDocumentItem()
        ));

        $types = [];

        foreach ($typesOfVar as $type) {
            $info = null;

            try {
                $info = $this->classlikeInfoBuilder->build($type);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            if (isset($info['constants'][$context->getNode()->name->name])) {
                $fetchedTypes = $this->fetchResolvedTypesFromTypeArrays(
                    $info['constants'][$context->getNode()->name->name]['types']
                );

                if (!empty($fetchedTypes)) {
                    $types += array_combine($fetchedTypes, array_fill(0, count($fetchedTypes), true));
                }
            }
        }

        // We use an associative array so we automatically avoid duplicate types.
        return array_keys($types);
    }
}
