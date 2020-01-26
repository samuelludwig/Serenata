<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\TypeAnalyzer;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * JsonRpcQueueItemHandlerthat shows information about a class, interface or trait.
 *
 * @deprecated Will be removed as soon as all functionality this facilitates is implemented as LSP-compliant requests.
 */
final class ClassInfoJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
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
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['name'])) {
            throw new InvalidArgumentsException(
                '"name" as fully qualified name of the classlike must be passed'
            );
        }

        $deferred = new Deferred();
        $deferred->resolve(new JsonRpcResponse(
            $queueItem->getRequest()->getId(),
            $this->getClassInfo($arguments['name'])
        ));

        return $deferred->promise();
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
