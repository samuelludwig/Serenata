<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Indexing\IndexDatabase;

use PhpIntegrator\Utility\NamespaceData;

/**
 * Command that shows a list of available namespace.
 */
class NamespaceListCommand extends AbstractCommand
{
    /**
     * @var IndexDatabase
     */
    private $indexDatabase;

    /**
     * @param IndexDatabase $indexDatabase
     */
    public function __construct(IndexDatabase $indexDatabase)
    {
        $this->indexDatabase = $indexDatabase;
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
        if ($file !== null) {
            $namespaces = $this->indexDatabase->getNamespacesForFile($file);

            return array_map(function (NamespaceData $namespaceData) {
                return [
                    'name'      => $namespaceData->getName(),
                    'startLine' => $namespaceData->getStartLine(),
                    'endLine'   => $namespaceData->getEndLine()
                ];
            }, $namespaces);
        }

        return $this->indexDatabase->getNamespaces($file);
    }
}
