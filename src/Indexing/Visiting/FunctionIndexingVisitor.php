<?php

namespace Serenata\Indexing\Visiting;

use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Analysis\Typing\TypeResolvingDocblockTypeTransformer;

use Serenata\Common\Range;
use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Parsing\DocblockTypeParserInterface;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use Serenata\Parsing\DocblockParser;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

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

        return null;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Function_) {
            $this->indexFunction($node);
        }

        return null;
    }

    /**
     * @param Node\Stmt\Function_ $node
     *
     * @return void
     */
    private function indexFunction(Node\Stmt\Function_ $node): void
    {
        $docComment = $node->getDocComment() !== null ? $node->getDocComment()->getText() : null;

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

        if ($documentation['return'] !== null && $documentation['return']['type'] !== null) {
            $typeStringSpecification = $documentation['return']['type'];
        } elseif ($node->getReturnType() !== null) {
            $nodeType = $node->getReturnType();

            if ($nodeType instanceof Node\NullableType) {
                $nodeType = $nodeType->type;
            }

            if ($nodeType instanceof Node\Name) {
                $typeStringSpecification = NodeHelpers::fetchClassName($nodeType);
            } elseif ($nodeType instanceof Node\Identifier) {
                $typeStringSpecification = $nodeType->name;
            }

            $nodeTypes = [];

            if ($nodeType instanceof Node\UnionType) {
                $nodeTypes = array_map(function ($nodeType): string {
                    return (string) $nodeType;
                }, $nodeType->types);
            } else {
                $nodeTypes = [$nodeType->toString()];
            }

            $typeStringSpecification = implode('|', $nodeTypes);

            if ($node->getReturnType() instanceof Node\NullableType) {
                $typeStringSpecification .= '|null';
            }
        }

        if ($typeStringSpecification) {
            $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

            $docblockType = $this->docblockTypeParser->parse($typeStringSpecification);

            $returnType = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);
        } elseif ($docComment !== null) {
            $returnType = new IdentifierTypeNode('void');
        } else {
            $returnType = new IdentifierTypeNode('mixed');
        }

        $throws = [];

        foreach ($documentation['throws'] as $throw) {
            $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

            $docblockType = $this->docblockTypeParser->parse($throw['type']);

            $localType = (string) $docblockType;

            $type = $this->typeResolvingDocblockTypeTransformer->resolve($docblockType, $filePosition);

            $throws[] = new Structures\ThrowsInfo(
                $localType,
                (string) $type,
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

            $defaultValue = $param->default !== null ?
                substr(
                    $this->textDocumentItem->getText(),
                    $param->default->getAttribute('startFilePos'),
                    $param->default->getAttribute('endFilePos') - $param->default->getAttribute('startFilePos') + 1
                ) :
                null;

            $parameterName = ($param->var instanceof Node\Expr\Variable ? $param->var->name : '');
            $parameterKey = '$' . (is_string($parameterName) ? $parameterName : 'unsupported');
            $parameterDoc = isset($documentation['params'][$parameterKey]) ?
                $documentation['params'][$parameterKey] : null;

            $unresolvedType = null;

            if ($parameterDoc) {
                $unresolvedType = $parameterDoc['type'];
            } elseif ($param->type !== null) {
                $typeNode = $param->type;

                if ($typeNode instanceof Node\NullableType) {
                    $typeNode = $typeNode->type;
                }

                if ($typeNode instanceof Node\Name) {
                    $unresolvedType = new IdentifierTypeNode(NodeHelpers::fetchClassName($typeNode));
                } elseif ($typeNode instanceof Node\Identifier) {
                    $unresolvedType = new IdentifierTypeNode($typeNode->name);
                } else /*if ($typeNode instanceof Node\UnionType)*/ {
                    $adaptedTypes = [];

                    foreach ($typeNode->types as $nestedTypeNode) {
                        if ($nestedTypeNode instanceof Node\Name) {
                            $adaptedTypes[] = new IdentifierTypeNode(NodeHelpers::fetchClassName($nestedTypeNode));
                        } else /*if ($nestedTypeNode instanceof Node\Identifier)*/ {
                            $adaptedTypes[] = new IdentifierTypeNode($nestedTypeNode->name);
                        }
                    }

                    $unresolvedType = new UnionTypeNode($adaptedTypes);
                }

                if ($param->type instanceof Node\NullableType) {
                    $unresolvedType = new UnionTypeNode([
                        $unresolvedType,
                        new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::NULL_),
                    ]);
                } elseif ($param->default instanceof Node\Expr\ConstFetch &&
                    $param->default->name->toString() === SpecialDocblockTypeIdentifierLiteral::NULL_
                ) {
                    $unresolvedType = new UnionTypeNode([
                        $unresolvedType,
                        new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::NULL_),
                    ]);
                }
            } elseif ($param->default !== null) {
                $unresolvedType = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                    $param->default,
                    $this->textDocumentItem
                ));
            }

            if ($unresolvedType !== null) {
                $filePosition = new FilePosition($this->textDocumentItem->getUri(), $range->getStart());

                $type = $this->typeResolvingDocblockTypeTransformer->resolve($unresolvedType, $filePosition);
            } else {
                $type = new IdentifierTypeNode('mixed');
            }

            if ($param->variadic) {
                $type = new ArrayTypeNode($type);
            }

            $parameter = new Structures\FunctionParameter(
                $function,
                is_string($parameterName) ? $parameterName : 'unsupported',
                $typeHint,
                $type,
                $parameterDoc ? $parameterDoc['description'] : null,
                $defaultValue,
                $param->byRef,
                $param->default !== null,
                $param->variadic
            );

            $this->storage->persist($parameter);
        }
    }
}
