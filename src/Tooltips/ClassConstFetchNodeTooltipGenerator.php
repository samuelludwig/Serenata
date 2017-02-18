<?php

namespace PhpIntegrator\Tooltips;

use UnexpectedValueException;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;
use PhpIntegrator\Analysis\GlobalConstantsProvider;
use PhpIntegrator\Analysis\ConstFetchNodeFqsenDeterminer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpParser\Node;

/**
 * Provides tooltips for {@see Node\Expr\ClassConstFetch} nodes.
 */
class ClassConstFetchNodeTooltipGenerator
{
    /**
     * @var ConstantTooltipGenerator
     */
    protected $constantTooltipGenerator;

    /**
     * @var NodeTypeDeducerInterface
     */
    protected $nodeTypeDeducer;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @param ConstantTooltipGenerator $constantTooltipGenerator
     * @param NodeTypeDeducerInterface $nodeTypeDeducer
     * @param ClasslikeInfoBuilder     $classlikeInfoBuilder
     */
    public function __construct(
        ConstantTooltipGenerator $constantTooltipGenerator,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilder $classlikeInfoBuilder
    ) {
        $this->constantTooltipGenerator = $constantTooltipGenerator;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param string                    $file
     * @param string                    $code
     *
     * @throws UnexpectedValueException when the constant name is not a string (i.e. an error node).
     * @throws UnexpectedValueException when the type of the class could not be determined.
     * @throws UnexpectedValueException when no tooltips could be determined.
     *
     * @return string
     */
    public function generate(Node\Expr\ClassConstFetch $node, string $file, string $code): string
    {
        if (!is_string($node->name)) {
            throw new UnexpectedValueException("Can't deduce the type of a non-string node");
        }

        $classTypes = [];

        try {
            $classTypes = $this->nodeTypeDeducer->deduce($node->class, $file, $code, $node->getAttribute('startFilePos'));
        } catch (UnexpectedValueException $e) {
            throw new UnexpectedValueException('Could not deduce the type of class', 0, $e);
        }

        if (empty($classTypes)) {
            throw new UnexpectedValueException('No types returned for class');
        }

        $tooltips = [];

        foreach ($classTypes as $classType) {
            $classInfo = null;

            try {
                $classInfo = $this->classlikeInfoBuilder->getClasslikeInfo($classType);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            if (!isset($classInfo['constants'][$node->name])) {
                continue;
            }

            $tooltips[] = $this->constantTooltipGenerator->generate($classInfo['constants'][$node->name]);
        }

        if (empty($tooltips)) {
            throw new UnexpectedValueException('Could not determine any tooltips for the class constant');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        return $tooltips[0];
    }
}
