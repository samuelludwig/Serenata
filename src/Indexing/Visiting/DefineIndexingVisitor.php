<?php

namespace Serenata\Indexing\Visiting;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\TypeDeductionException;

use Serenata\Analysis\Typing\TypeResolvingDocblockTypeTransformer;

use Serenata\Common\Range;
use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Parsing\DocblockParser;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

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
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

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
     * @param DocblockParser                       $docblockParser
     * @param NodeTypeDeducerInterface             $nodeTypeDeducer
     * @param TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer
     * @param Structures\File                      $file
     * @param TextDocumentItem                     $textDocumentItem
     */
    public function __construct(
        StorageInterface $storage,
        DocblockParser $docblockParser,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer,
        Structures\File $file,
        TextDocumentItem $textDocumentItem
    ) {
        $this->storage = $storage;
        $this->docblockParser = $docblockParser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->typeResolvingDocblockTypeTransformer = $typeResolvingDocblockTypeTransformer;
        $this->file = $file;
        $this->textDocumentItem = $textDocumentItem;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\FuncCall &&
            $node->name instanceof Node\Name &&
            $node->name->toString() === 'define'
        ) {
            $this->parseDefineNode($node);
        }

        return null;
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

        $docComment = $node->getDocComment() !== null ? $node->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::VAR_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION,
        ], $nameValue->value);

        $varDocumentation = isset($documentation['var']['$' . $nameValue->value]) ?
            $documentation['var']['$' . $nameValue->value] :
            null;

        $shortDescription = $documentation['descriptions']['short'];

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

        $unresolvedType = null;

        if ($varDocumentation) {
            // You can place documentation after the @var tag as well as at the start of the docblock. Fall back
            // from the latter to the former.
            if ($varDocumentation['description'] !== '' && $varDocumentation['description'] !== null) {
                $shortDescription = $varDocumentation['description'];
            }

            $unresolvedType = $varDocumentation['type'];
        } elseif (isset($node->args[1])) {
            try {
                $unresolvedType = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                    $node->args[1]->value,
                    $this->textDocumentItem
                ));
            } catch (TypeDeductionException $th) {
                $unresolvedType = new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::MIXED_);
            }
        }

        $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

        if ($unresolvedType !== null) {
            $type = $this->typeResolvingDocblockTypeTransformer->resolve($unresolvedType, $filePosition);
        } else {
            $type = new IdentifierTypeNode('mixed');
        }

        // Defines can be namespaced if their name contains slashes, see also
        // https://php.net/manual/en/function.define.php#90282
        $name = new Node\Name($nameValue->value);

        $constant = new Structures\Constant(
            $name->getLast(),
            '\\' . NodeHelpers::fetchClassName($name),
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
