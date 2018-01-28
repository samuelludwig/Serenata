<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\ClasslikeInfoBuilderInterface;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Indexing\Structures;

/**
 * Factory that produces instances of {@see UnknownMemberAnalyzer}.
 */
class UnknownMemberAnalyzerFactory
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
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @param NodeTypeDeducerInterface      $nodeTypeDeducer
     * @param ClasslikeInfoBuilderInterface $classlikeInfoBuilder
     * @param TypeAnalyzer                  $typeAnalyzer
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        TypeAnalyzer $typeAnalyzer
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->typeAnalyzer = $typeAnalyzer;
    }

    /**
     * @param Structures\File $file
     * @param string          $code
     *
     * @return UnknownMemberAnalyzer
     */
    public function create(Structures\File $file, string $code): UnknownMemberAnalyzer
    {
        return new UnknownMemberAnalyzer(
            $this->nodeTypeDeducer,
            $this->classlikeInfoBuilder,
            $this->typeAnalyzer,
            $file,
            $code
        );
    }
}
