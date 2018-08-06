<?php

namespace Serenata\Indexing\Visiting;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;

use Serenata\Analysis\Typing\TypeResolvingDocblockTypeTransformer;

use Serenata\Common\Range;
use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\DocblockTypeParser\MixedDocblockType;
use Serenata\DocblockTypeParser\DocblockTypeParserInterface;

use Serenata\Utility\PositionEncoding;

use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use Serenata\Utility\NodeHelpers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use Serenata\Utility\TextDocumentItem;

/**
 * Visitor that traverses a set of nodes, indexing defines in the process.
 */
final class DefineIndexingVisitor extends NodeVisitorAbstract
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * @var TypeResolvingDocblockTypeTransformer
     */
    private $typeResolvingDocblockTypeTransformer;

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
     * @param NodeTypeDeducerInterface             $nodeTypeDeducer
     * @param DocblockTypeParserInterface          $docblockTypeParser
     * @param TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer
     * @param Structures\File                      $file
     * @param TextDocumentItem                     $textDocumentItem
     */
    public function __construct(
        StorageInterface $storage,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        DocblockTypeParserInterface $docblockTypeParser,
        TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer,
        Structures\File $file,
        TextDocumentItem $textDocumentItem
    ) {
        $this->storage = $storage;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->docblockTypeParser = $docblockTypeParser;
        $this->typeResolvingDocblockTypeTransformer = $typeResolvingDocblockTypeTransformer;
        $this->file = $file;
        $this->textDocumentItem = $textDocumentItem;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Expr\FuncCall &&
            $node->name instanceof Node\Name &&
            $node->name->toString() === 'define'
        ) {
            $this->parseDefineNode($node);
        }
    }

    /**
     * @param Node\Expr\FuncCall $node
     *
     * @return void
     */
    private function parseDefineNode(Node\Expr\FuncCall $node): void
    {
        if (count($node->args) < 2) {
            return;
        }

        $nameValue = $node->args[0]->value;

        if (!$nameValue instanceof Node\Scalar\String_) {
            return;
        }

        // Defines can be namespaced if their name contains slashes, see also
        // https://php.net/manual/en/function.define.php#90282
        $name = new Node\Name((string) $nameValue->value);

        $type = new MixedDocblockType();

        $defaultValue = substr(
            $this->textDocumentItem->getText(),
            $node->args[1]->getAttribute('startFilePos'),
            $node->args[1]->getAttribute('endFilePos') - $node->args[1]->getAttribute('startFilePos') + 1
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

        if (isset($node->args[1])) {
            $typeList = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                $node->args[1]->value,
                $this->textDocumentItem
            ));

            if (count($typeList) !== 0) {
                $typeStringSpecification = implode('|', $typeList);

                $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

                $docblockType = $this->docblockTypeParser->parse($typeStringSpecification);

                $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
            }
        }

        $constant = new Structures\Constant(
            $name->getLast(),
            '\\' . NodeHelpers::fetchClassName($name),
            $this->file,
            $range,
            $defaultValue,
            false,
            false,
            null,
            null,
            null,
            $type
        );

        $this->storage->persist($constant);
    }
}
