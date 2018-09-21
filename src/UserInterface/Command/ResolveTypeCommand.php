<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Indexing\StorageInterface;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that resolves local types in a file.
 */
final class ResolveTypeCommand extends AbstractCommand
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @param StorageInterface                           $storage
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     */
    public function __construct(
        StorageInterface $storage,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
    ) {
        $this->storage = $storage;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['type'])) {
            throw new InvalidArgumentsException('"type" must be supplied');
        } elseif (!isset($arguments['uri'])) {
            throw new InvalidArgumentsException('"uri" must be supplied');
        } elseif (!isset($arguments['position'])) {
            throw new InvalidArgumentsException('"position" must be supplied');
        }

        $position = new Position($arguments['position']['line'], $arguments['position']['character']);

        $type = $this->resolveType(
            $arguments['type'],
            $arguments['uri'],
            $position,
            isset($arguments['kind']) ? $arguments['kind'] : UseStatementKind::TYPE_CLASSLIKE
        );

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $type);
    }

    /**
     * Resolves the type.
     *
     * @param string   $name
     * @param string   $uri
     * @param Position $position
     * @param string   $kind     A constant from {@see UseStatementKind}.
     *
     * @throws InvalidArgumentsException
     *
     * @return string|null
     */
    public function resolveType(string $name, string $uri, Position $position, string $kind): ?string
    {
        $recognizedKinds = [
            UseStatementKind::TYPE_CLASSLIKE,
            UseStatementKind::TYPE_FUNCTION,
            UseStatementKind::TYPE_CONSTANT,
        ];

        if (!in_array($kind, $recognizedKinds)) {
            throw new InvalidArgumentsException('Unknown "kind" specified');
        }

        // Not used (yet), but still throws an exception when file is not in index.
        $this->storage->getFileByUri($uri);

        $filePosition = new FilePosition($uri, $position);

        return $this->structureAwareNameResolverFactory->create($filePosition)->resolve($name, $filePosition, $kind);
    }
}
