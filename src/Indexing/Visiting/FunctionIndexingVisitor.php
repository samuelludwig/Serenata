<?php

namespace Serenata\Indexing\Visiting;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Analysis\Typing\TypeResolvingDocblockTypeTransformer;

use Serenata\Common\Range;
use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\DocblockTypeParser\VoidDocblockType;
use Serenata\DocblockTypeParser\MixedDocblockType;
use Serenata\DocblockTypeParser\DocblockTypeParserInterface;
use Serenata\DocblockTypeParser\SpecializedArrayDocblockType;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use Serenata\Parsing\DocblockParser;

use Serenata\Utility\NodeHelpers;
use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Visitor that traverses a set of nodes, indexing (global) functions in the process.
 */
final class FunctionIndexingVisitor extends NodeVisitorAbstract
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
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

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
     * @param DocblockTypeParserInterface          $docblockTypeParser
     * @param TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer
     * @param NodeTypeDeducerInterface             $nodeTypeDeducer
     * @param Structures\File                      $file
     * @param TextDocumentItem                     $textDocumentItem
     */
    public function __construct(
        StorageInterface $storage,
        DocblockParser $docblockParser,
        DocblockTypeParserInterface $docblockTypeParser,
        TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        Structures\File $file,
        TextDocumentItem $textDocumentItem
    ) {
        $this->storage = $storage;
        $this->docblockParser = $docblockParser;
        $this->docblockTypeParser = $docblockTypeParser;
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
        foreach ($this->file->getFunctions() as $function) {
            $this->file->removeFunction($function);

            $this->storage->delete($function);
        }
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Function_) {
            $this->indexFunction($node);
        }
    }

    /**
     * @param Node\Stmt\Function_ $node
     *
     * @return void
     */
    private function indexFunction(Node\Stmt\Function_ $node): void
    {
        $docComment = $node->getDocComment() ? $node->getDocComment()->getText() : null;

        $returnTypeHint = null;
        $nodeType = $node->getReturnType();

        if ($nodeType instanceof Node\NullableType) {
            $returnTypeHint = '?';
            $nodeType = $nodeType->type;
        }

        if ($nodeType instanceof Node\Name) {
            $returnTypeHint .= NodeHelpers::fetchClassName($nodeType->getAttribute('resolvedName'));
        } elseif ($nodeType instanceof Node\Identifier) {
            $returnTypeHint .= $nodeType->name;
        }

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

        $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::THROWS,
            DocblockParser::PARAM_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION,
            DocblockParser::RETURN_VALUE,
        ], $node->name);

        $typeStringSpecification = null;

        if ($documentation && $documentation['return'] !== null && $documentation['return']['type'] !== null) {
            $typeStringSpecification = $documentation['return']['type'];
        } elseif ($node->getReturnType()) {
            $nodeType = $node->getReturnType();

            if ($nodeType instanceof Node\NullableType) {
                $nodeType = $nodeType->type;
            }

            if ($nodeType instanceof Node\Name) {
                $typeStringSpecification = NodeHelpers::fetchClassName($nodeType);
            } elseif ($nodeType instanceof Node\Identifier) {
                $typeStringSpecification = $nodeType->name;
            }

            $typeStringSpecification = $nodeType->toString();

            if ($node->getReturnType() instanceof Node\NullableType) {
                $typeStringSpecification .= '|null';
            }
        }

        if ($typeStringSpecification) {
            $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

            $docblockType = $this->docblockTypeParser->parse($typeStringSpecification);

            $returnType = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
        } elseif ($docComment) {
            $returnType = new VoidDocblockType();
        } else {
            $returnType = new MixedDocblockType();
        }

        $throws = [];

        foreach ($documentation['throws'] as $throw) {
            $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

            $docblockType = $this->docblockTypeParser->parse($throw['type']);

            $localType = $docblockType->toString();

            $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);

            $throws[] = new Structures\ThrowsInfo(
                $localType,
                $type->toString(),
                $throw['description'] !== '' ? $throw['description'] : null
            );
        }

        $function = new Structures\Function_(
            $node->name,
            '\\' . $node->namespacedName->toString(),
            $this->file,
            $range,
            $documentation['deprecated'],
            $documentation['descriptions']['short'] !== '' ? $documentation['descriptions']['short'] : null,
            $documentation['descriptions']['long'] !== '' ? $documentation['descriptions']['long'] : null,
            $documentation['return']['description'] ?? null,
            $returnTypeHint,
            $docComment !== '' && $docComment !== null,
            $throws,
            $returnType
        );

        $this->storage->persist($function);

        foreach ($node->getParams() as $param) {
            $typeHint = null;
            $typeNode = $param->type;

            if ($typeNode instanceof Node\NullableType) {
                $typeHint = '?';
                $typeNode = $typeNode->type;
            }

            if ($typeNode instanceof Node\Name) {
                $typeHint .= NodeHelpers::fetchClassName($typeNode->getAttribute('resolvedName'));
            } elseif ($typeNode instanceof Node\Identifier) {
                $typeHint .= $typeNode->name;
            }

            $isNullable = (
                ($param->type instanceof Node\NullableType) ||
                ($param->default instanceof Node\Expr\ConstFetch && $param->default->name->toString() === 'null')
            );

            $defaultValue = $param->default ?
                substr(
                    $this->textDocumentItem->getText(),
                    $param->default->getAttribute('startFilePos'),
                    $param->default->getAttribute('endFilePos') - $param->default->getAttribute('startFilePos') + 1
                ) :
                null;

            $parameterName = ($param->var instanceof Node\Expr\Variable ? $param->var->name : '');
            $parameterKey = '$' . $parameterName;
            $parameterDoc = isset($documentation['params'][$parameterKey]) ?
                $documentation['params'][$parameterKey] : null;

            $typeStringSpecification = null;

            if ($parameterDoc) {
                $typeStringSpecification = $parameterDoc['type'];
            } elseif ($param->type) {
                $typeNode = $param->type;

                if ($typeNode instanceof Node\NullableType) {
                    $typeNode = $typeNode->type;
                }

                if ($typeNode instanceof Node\Name) {
                    $typeStringSpecification = NodeHelpers::fetchClassName($typeNode);
                } elseif ($typeNode instanceof Node\Identifier) {
                    $typeStringSpecification = $typeNode->name;
                }

                if ($param->type instanceof Node\NullableType) {
                    $typeStringSpecification .= '|null';
                } elseif ($param->default instanceof Node\Expr\ConstFetch &&
                    $param->default->name->toString() === 'null'
                ) {
                    $typeStringSpecification .= '|null';
                }
            } elseif ($param->default !== null) {
                $typeList = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                    $param->default,
                    $this->textDocumentItem
                ));

                $typeStringSpecification = implode('|', $typeList);
            }

            if ($typeStringSpecification) {
                $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

                $docblockType = $this->docblockTypeParser->parse($typeStringSpecification);

                $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
            } else {
                $type = new MixedDocblockType();
            }

            if ($param->variadic) {
                $type = new SpecializedArrayDocblockType($type);
            }

            $parameter = new Structures\FunctionParameter(
                $function,
                $parameterName,
                $typeHint,
                $type,
                $parameterDoc ? $parameterDoc['description'] : null,
                $defaultValue,
                $param->byRef,
                !!$param->default,
                $param->variadic
            );

            $this->storage->persist($parameter);
        }
    }
}
