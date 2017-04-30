<?php

namespace PhpIntegrator\Indexing\Visiting;

use UnexpectedValueException;

use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\IndexStorageItemEnum;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that traverses a set of nodes, indexing data as meta file data in the process.
 */
class MetaFileIndexingVisitor extends NodeVisitorAbstract
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var int
     */
    private $fileId;

    /**
     * @param StorageInterface $storage
     * @param int              $fileId
     */
    public function __construct(StorageInterface $storage, int $fileId)
    {
        $this->storage = $storage;
        $this->fileId = $fileId;
    }

    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\Assign) {
            $this->enterAssignNode($node);
        }
    }

    /**
     * @param Node\Expr\Assign $node
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    protected function enterAssignNode(Node\Expr\Assign $node): void
    {
        if ($node->var instanceof Node\Expr\Variable && $node->var->name === 'STATIC_METHOD_TYPES') {
            $this->enterStaticMethodTypesAssignNode($node);
        }
    }

    /**
     * @param Node\Expr\Assign $node
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    protected function enterStaticMethodTypesAssignNode(Node\Expr\Assign $node): void
    {
        if (!$node->expr instanceof Node\Expr\Array_) {
            throw new UnexpectedValueException('$STATIC_METHOD_TYPES be an array');
        }

        foreach ($node->expr->items as $item) {
            $this->enterStaticMethodTypesElementNode($item);
        }
    }

    /**
     * @param Node\Expr\ArrayItem $node
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    protected function enterStaticMethodTypesElementNode(Node\Expr\ArrayItem $node): void
    {
        if (!$node->key instanceof Node\Expr\StaticCall) {
            throw new UnexpectedValueException('Key of each element must be a static method call');
        } elseif (!$node->key->class instanceof Node\Name) {
            throw new UnexpectedValueException(
                'Static method call used as key must not use a dynamic expression as class name'
            );
        } elseif (!is_string($node->key->name)) {
            throw new UnexpectedValueException(
                'Static method call used as key must not use a dynamic expression as method name'
            );
        } elseif (!$node->value instanceof Node\Expr\Array_) {
            throw new UnexpectedValueException('Value of each element must be another array');
        }

        $resolvedName = $node->key->class->getAttribute('resolvedName');

        $fqcn = NodeHelpers::fetchClassName($resolvedName);
        $name = $node->key->name;
        $argumentIndex = 0;

        foreach ($node->value->items as $item) {
            $this->enterStaticMethodTypesValueNode($item, $fqcn, $name, $argumentIndex);
        }
    }

    /**
     * @param Node\Expr\ArrayItem $item
     * @param string              $fqcn
     * @param string              $name
     * @param int                 $argumentIndex
     *
     * @return void
     */
    protected function enterStaticMethodTypesValueNode(
        Node\Expr\ArrayItem $item,
        string $fqcn,
        string $name,
        int $argumentIndex
    ): void {
        if (!$item->value instanceof Node\Expr\Instanceof_) {
            throw new UnexpectedValueException(
                'Value must be an instanceof with string value on left side and FQCN on right side'
            );
        } elseif (!$item->value->expr instanceof Node\Scalar\String_) {
            throw new UnexpectedValueException('instanceof must have string value on left side');
        } elseif (!$item->value->class instanceof Node\Name) {
            throw new UnexpectedValueException('instanceof must have FQCN on right side');
        }

        $resolvedName = $item->value->class->getAttribute('resolvedName');
        $returnType = NodeHelpers::fetchClassName($resolvedName);

        $this->storage->insert(IndexStorageItemEnum::META_STATIC_METHOD_TYPES, [
            'file_id'         => $this->fileId,
            'fqcn'            => $fqcn,
            'name'            => $name,
            'argument_index'  => $argumentIndex,
            'value'           => $item->value->expr->value,
            'value_node_type' => Node\Scalar\String_::class,
            'return_type'     => $returnType
        ]);
    }
}
