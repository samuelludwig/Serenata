<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\TypeAnalyzer;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that shows information about a class, interface or trait.
 */
final class ClassInfoCommand extends AbstractCommand
{
    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @param TypeAnalyzer                  $typeAnalyzer
     * @param ClasslikeInfoBuilderInterface $classlikeInfoBuilder
     */
    public function __construct(TypeAnalyzer $typeAnalyzer, ClasslikeInfoBuilderInterface $classlikeInfoBuilder)
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

        return $this->classlikeInfoBuilder->build($fqcn);
    }
}
