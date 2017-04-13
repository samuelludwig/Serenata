<?php

namespace PhpIntegrator\Indexing;

use DateTime;
use Exception;
use UnexpectedValueException;

use PhpIntegrator\Analysis\Visiting\ResolvedNameAttachingVisitor;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;
use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Handles indexation of a PHP meta file.
 */
class MetaFileIndexer extends NodeVisitorAbstract implements FileIndexerInterface
{
    /**
     * The storage to use for index data.
     *
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var int
     */
    private $fileId;

    /**
     * @param StorageInterface $storage
     * @param Parser           $parser
     */
    public function __construct(StorageInterface $storage, Parser $parser)
    {
        $this->storage = $storage;
        $this->parser = $parser;
    }

    /**
     * @inheritDoc
     */
    public function index(string $filePath, string $code): void
    {
        $handler = new ErrorHandler\Collecting();

        try {
            $nodes = $this->parser->parse($code, $handler);

            if ($nodes === null) {
                throw new Error('Unknown syntax error encountered');
            }
        } catch (Error $e) {
            throw new IndexingFailedException('The code could not be parsed', 0, $e);
        }

        $this->storage->beginTransaction();

        $this->storage->deleteFile($filePath);

        $this->fileId = $this->storage->insert(IndexStorageItemEnum::FILES, [
            'path'         => $filePath,
            'indexed_time' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        try {
            $traverser = new NodeTraverser(false);
            $traverser->addVisitor($this);
            $traverser->traverse($nodes);

            $this->storage->commitTransaction();
        } catch (Exception $e) {
            $this->storage->rollbackTransaction();

            throw new IndexingFailedException($e->getMessage(), 0, $e);
        }
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
