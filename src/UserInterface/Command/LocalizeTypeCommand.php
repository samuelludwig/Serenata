<?php

namespace Serenata\UserInterface\Command;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Indexing\StorageInterface;

use Serenata\NameQualificationUtilities\PositionalNameLocalizerFactoryInterface;

use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcQueueItem;

/**
 * Command that makes a FQCN relative to local use statements in a file.
 */
final class LocalizeTypeCommand extends AbstractCommand
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
    public function execute(JsonRpcQueueItem $queueItem): ?JsonRpcResponse
    {
        $arguments = $queueItem->getRequest()->getParams() ?: [];

        if (!isset($arguments['type'])) {
            throw new InvalidArgumentsException('The type is required for this command.');
        } elseif (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A file name is required for this command.');
        } elseif (!isset($arguments['line'])) {
            throw new InvalidArgumentsException('A line number is required for this command.');
        }

        $type = $this->localizeType(
            $arguments['type'],
            $arguments['file'],
            $arguments['line'],
            isset($arguments['kind']) ? $arguments['kind'] : UseStatementKind::TYPE_CLASSLIKE
        );

        return new JsonRpcResponse($queueItem->getRequest()->getId(), $type);
    }

    /**
     * Resolves the type.
     *
     * @param string $type
     * @param string $filePath
     * @param int    $line
     * @param string $kind     A constant from {@see UseStatementKind}.
     *
     * @return string|null
     */
    public function localizeType(string $type, string $filePath, int $line, string $kind): ?string
    {
        $file = $this->storage->getFileByPath($filePath);

        $filePosition = new FilePosition($file->getPath(), new Position($line, 0));

        return $this->positionalNameLocalizerFactory->create($filePosition)->localize($type, $kind);
    }
}
