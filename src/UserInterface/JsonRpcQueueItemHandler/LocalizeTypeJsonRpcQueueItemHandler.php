<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Indexing\StorageInterface;

use Serenata\NameQualificationUtilities\PositionalNameLocalizerFactoryInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * JsonRpcQueueItemHandlerthat makes a FQCN relative to local use statements in a file.
 */
final class LocalizeTypeJsonRpcQueueItemHandler extends AbstractJsonRpcQueueItemHandler
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var PositionalNameLocalizerFactoryInterface
     */
    private $positionalNameLocalizerFactory;

    /**
     * @param StorageInterface                        $storage
     * @param PositionalNameLocalizerFactoryInterface $positionalNameLocalizerFactory
     */
    public function __construct(
        StorageInterface $storage,
        PositionalNameLocalizerFactoryInterface $positionalNameLocalizerFactory
    ) {
        $this->storage = $storage;
        $this->positionalNameLocalizerFactory = $positionalNameLocalizerFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ExtendedPromiseInterface
    {
        $arguments = $queueItem->getRequest()->getParams() !== null ?
            $queueItem->getRequest()->getParams() :
            [];

        if (!isset($arguments['type'])) {
            throw new InvalidArgumentsException('"type" must be supplied');
        } elseif (!isset($arguments['uri'])) {
            throw new InvalidArgumentsException('"uri" must be supplied');
        } elseif (!isset($arguments['position'])) {
            throw new InvalidArgumentsException('"position" must be supplied');
        }

        $position = new Position($arguments['position']['line'], $arguments['position']['character']);

        $type = $this->localizeType(
            $arguments['type'],
            $arguments['uri'],
            $position,
            isset($arguments['kind']) ? $arguments['kind'] : UseStatementKind::TYPE_CLASSLIKE
        );

        $deferred = new Deferred();
        $deferred->resolve(new JsonRpcResponse($queueItem->getRequest()->getId(), $type));

        return $deferred->promise();
    }

    /**
     * @param string   $type
     * @param string   $uri
     * @param Position $position
     * @param string   $kind     A constant from {@see UseStatementKind}.
     *
     * @return string|null
     */
    public function localizeType(string $type, string $uri, Position $position, string $kind): ?string
    {
        // Not used (yet), but still throws an exception when file is not in index.
        $this->storage->getFileByUri($uri);

        $filePosition = new FilePosition($uri, $position);

        return $this->positionalNameLocalizerFactory->create($filePosition)->localize($type, $kind);
    }
}
