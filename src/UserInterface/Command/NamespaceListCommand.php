<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Indexing\Structures;
use PhpIntegrator\Indexing\ManagerRegistry;

/**
 * Command that shows a list of available namespace.
 */
class NamespaceListCommand extends AbstractCommand
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
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
            $fileEntity = $this->managerRegistry->getRepository(Structures\File::class)->findOneBy([
                'path' => $file
            ]);

            if ($fileEntity === null) {
                throw new InvalidArgumentsException("File \"{$file}\" is not present in the index");
            }

            $criteria['file'] = $fileEntity;
        }

        $namespaces = $this->managerRegistry->getRepository(Structures\FileNamespace::class)->findBy($criteria);

        return array_map(function (Structures\FileNamespace $namespace) {
            return [
                'name'      => $namespace->getName(),
                'startLine' => $namespace->getStartLine(),
                'endLine'   => $namespace->getEndLine()
            ];
        }, $namespaces);
    }
}
