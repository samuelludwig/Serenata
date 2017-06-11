<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\NamespaceListProviderInterface;
use PhpIntegrator\Analysis\FileNamespaceListProviderInterface;

/**
 * Command that shows a list of available namespace.
 */
class NamespaceListCommand extends AbstractCommand
{
    /**
     * @var NamespaceListProviderInterface
     */
    private $namespaceListProvider;

    /**
     * @var FileNamespaceListProviderInterface
     */
    private $fileNamespaceListProvider;

    /**
     * @param NamespaceListProviderInterface     $namespaceListProvider
     * @param FileNamespaceListProviderInterface $fileNamespaceListProvider
     */
    public function __construct(
        NamespaceListProviderInterface $namespaceListProvider,
        FileNamespaceListProviderInterface $fileNamespaceListProvider
    ) {
        $this->namespaceListProvider = $namespaceListProvider;
        $this->fileNamespaceListProvider = $fileNamespaceListProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        $file = isset($arguments['file']) ? $arguments['file'] : null;

        $list = $this->getNamespaceList($file);

        return $list;
    }

    /**
     * @param string|null $file
     *
     * @return array
     */
    public function getNamespaceList(?string $file = null): array
    {
        $criteria = [];

        if ($file !== null) {
            return $this->fileNamespaceListProvider->getAllForFile($file);
        }

        return $this->namespaceListProvider->getAll();
    }
}
