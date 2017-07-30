<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcQueueItem;

/**
 * Command that shows information about a class, interface or trait.
 */
class ClassInfoCommand extends AbstractCommand
{
    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var ClasslikeInfoBuilder
     */
    private $classlikeInfoBuilder;

    /**
     * @param TypeAnalyzer         $typeAnalyzer
     * @param ClasslikeInfoBuilder $classlikeInfoBuilder
     */
    public function __construct(TypeAnalyzer $typeAnalyzer, ClasslikeInfoBuilder $classlikeInfoBuilder)
    {
        $this->typeAnalyzer = $typeAnalyzer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['name'])) {
            throw new InvalidArgumentsException(
                'The fully qualified name of the structural element is required for this command.'
            );
        }

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $this->getClassInfo($arguments['name']));
    }

    /**
     * @param string $fqcn
     *
     * @return array
     */
    public function getClassInfo(string $fqcn): array
    {
        $fqcn = $this->typeAnalyzer->getNormalizedFqcn($fqcn);

        return $this->classlikeInfoBuilder->getClasslikeInfo($fqcn);
    }
}
