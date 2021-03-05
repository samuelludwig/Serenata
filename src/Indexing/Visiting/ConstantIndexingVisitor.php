<?php

namespace Serenata\Indexing\Visiting;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\TypeDeductionException;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Analysis\Typing\TypeResolvingDocblockTypeTransformer;

use Serenata\Common\Range;
use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use Serenata\Parsing\DocblockParser;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Visitor that traverses a set of nodes, indexing (global) constants in the process.
 */
final class ConstantIndexingVisitor extends NodeVisitorAbstract
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var TypeResolvingDocblockTypeTransformer
     */
    private $typeResolvingDocblockTypeTransformer;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var Structures\File
     */
    private $file;

    /**
     * @var TextDocumentItem
     */
    private $textDocumentItem;

    /**
     * @param StorageInterface                     $storage
     * @param DocblockParser                       $docblockParser
     * @param TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer
     * @param NodeTypeDeducerInterface             $nodeTypeDeducer
     * @param Structures\File                      $file
     * @param TextDocumentItem                     $textDocumentItem
     */
    public function __construct(
        StorageInterface $storage,
        DocblockParser $docblockParser,
        TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        Structures\File $file,
        TextDocumentItem $textDocumentItem
    ) {
        $this->storage = $storage;
        $this->docblockParser = $docblockParser;
        $this->typeResolvingDocblockTypeTransformer = $typeResolvingDocblockTypeTransformer;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->file = $file;
        $this->textDocumentItem = $textDocumentItem;
    }

    /**
     * @inheritDoc
     */
    public function beforeTraverse(array $nodes)
    {
        foreach ($this->file->getConstants() as $constant) {
            $this->file->removeConstant($constant);

            $this->storage->delete($constant);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Const_) {
            $this->parseConstantStatementNode($node);
        }

        return null;
    }

    /**
     * @param Node\Stmt\Const_ $node
     *
     * @return void
     */
    private function parseConstantStatementNode(Node\Stmt\Const_ $node): void
    {
        foreach ($node->consts as $const) {
            $this->indexConstant($const, $node);
        }
    }

    /**
     * @param Node\Const_      $node
     * @param Node\Stmt\Const_ $const
     *
     * @return void
     */
    private function indexConstant(Node\Const_ $node, Node\Stmt\Const_ $const): void
    {
        $docComment = $const->getDocComment() !== null ? $const->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::VAR_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION,
        ], $node->name);

        $varDocumentation = isset($documentation['var']['$' . $node->name]) ?
            $documentation['var']['$' . $node->name] :
            null;

        $shortDescription = $documentation['descriptions']['short'];

        $types = [];

        $defaultValue = substr(
            $this->textDocumentItem->getText(),
            $node->value->getAttribute('startFilePos'),
            $node->value->getAttribute('endFilePos') - $node->value->getAttribute('startFilePos') + 1
        );

        $range = new Range(
            Position::createFromByteOffset(
                $node->getAttribute('startFilePos'),
                $this->textDocumentItem->getText(),
                PositionEncoding::VALUE
            ),
            Position::createFromByteOffset(
                $node->getAttribute('endFilePos') + 1,
                $this->textDocumentItem->getText(),
                PositionEncoding::VALUE
            )
        );

        $unresolvedType = null;

        if ($varDocumentation) {
            // You can place documentation after the @var tag as well as at the start of the docblock. Fall back
            // from the latter to the former.
            if ($varDocumentation['description'] !== '' && $varDocumentation['description'] !== null) {
                $shortDescription = $varDocumentation['description'];
            }

            $unresolvedType = $varDocumentation['type'];
        } else {
            try {
                $unresolvedType = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                    $node->value,
                    $this->textDocumentItem
                ));
            } catch (TypeDeductionException $e) {
                $unresolvedType = null;
            }
        }

        if ($unresolvedType !== null) {
            $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

            $type = $this->typeResolvingDocblockTypeTransformer->resolve($unresolvedType, $filePosition);
        } else {
            $type = new IdentifierTypeNode('mixed');
        }

        $constant = new Structures\Constant(
            $node->name,
            '\\' . $node->namespacedName->toString(),
            $this->file,
            $range,
            $defaultValue,
            $documentation['deprecated'],
            $docComment !== '' && $docComment !== null,
            $shortDescription ? $shortDescription : null,
            $documentation['descriptions']['long'] ? $documentation['descriptions']['long'] : null,
            $varDocumentation ? $varDocumentation['description'] : null,
            $type
        );

        $this->storage->persist($constant);
    }
}
