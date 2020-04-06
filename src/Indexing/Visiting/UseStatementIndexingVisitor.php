<?php

namespace Serenata\Indexing\Visiting;

use Serenata\Analysis\Visiting\UseStatementFetchingVisitor;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use PhpParser\Node;
use PhpParser\NodeVisitor;

/**
 * Visitor that traverses a set of nodes and indexes use statements and namespaces in the process.
 */
final class UseStatementIndexingVisitor implements NodeVisitor
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var Structures\File
     */
    private $file;

    /**
     * @var UseStatementFetchingVisitor
     */
    private $useStatementFetchingVisitor;

    /**
     * @param StorageInterface $storage
     * @param Structures\File  $file
     * @param string           $code
     */
    public function __construct(StorageInterface $storage, Structures\File $file, string $code)
    {
        $this->storage = $storage;
        $this->file = $file;

        $this->useStatementFetchingVisitor = new UseStatementFetchingVisitor($code);
    }

    /**
     * @inheritDoc
     */
    public function beforeTraverse(array $nodes)
    {
        // TODO: Probably this deletes items, which aren't flushed yet, but schedule events. Later during commit, it just
        // so HAPPENS that the namespace list registry hasne't been inited yet. The remove causes it to init, returning
        // the just-removed namespace with a dead FK file link for Doctrine, which it then needs to do namespace
        // convert,causing the error.
        // Need working test case first. Possibly fixable by just not loading the registries for removals.
        foreach ($this->file->getNamespaces() as $namespace) {
            $this->file->removeNamespace($namespace);

            $this->storage->delete($namespace);
        }

        $this->useStatementFetchingVisitor->beforeTraverse($nodes);

        return null;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        $this->useStatementFetchingVisitor->enterNode($node);

        return null;
    }

    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
        $this->useStatementFetchingVisitor->leaveNode($node);

        return null;
    }

    /**
     * @inheritDoc
     */
    public function afterTraverse(array $nodes)
    {
        $this->useStatementFetchingVisitor->afterTraverse($nodes);

        foreach ($this->useStatementFetchingVisitor->getNamespaces() as $namespace) {
            $this->indexNamespace($namespace);
        }

        return null;
    }

    /**
     * @param array<string,mixed> $namespace
     */
    private function indexNamespace(array $namespace): void
    {
        $namespaceEntity = new Structures\FileNamespace(
            $namespace['range'],
            $namespace['name'],
            $this->file,
            []
        );

        $this->storage->persist($namespaceEntity);

        foreach ($namespace['useStatements'] as $useStatement) {
            $this->indexUseStatement($useStatement, $namespaceEntity);
        }
    }

    /**
     * @param array<string,mixed>      $useStatement
     * @param Structures\FileNamespace $namespace
     */
    private function indexUseStatement(array $useStatement, Structures\FileNamespace $namespace): void
    {
        $import = new Structures\FileNamespaceImport(
            $useStatement['range'],
            $useStatement['alias'] !== '' ? $useStatement['alias'] : null,
            $useStatement['name'],
            $useStatement['kind'],
            $namespace
        );

        $this->storage->persist($import);
    }
}
