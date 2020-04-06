<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;

use Serenata\Parsing\DocblockParser;
use Serenata\Parsing\InvalidTypeNode;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

use Serenata\Utility\NodeHelpers;

/**
 * Type deducer that can deduce the type of a parameter of a {@see Node\FunctionLike} node.
 */
final class FunctionLikeParameterTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var string|null
     */
    private $functionDocblock;

    /**
     * @param DocblockParser $docblockParser
     */
    public function __construct(DocblockParser $docblockParser)
    {
        $this->docblockParser = $docblockParser;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Param) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $varNode = $context->getNode()->var;

        if ($varNode instanceof Node\Expr\Error) {
            return new InvalidTypeNode();
        } elseif ($varNode->name instanceof Node\Expr) {
            return new InvalidTypeNode();
        }

        $docBlock = $this->getFunctionDocblock();

        if ($docBlock !== null && $docBlock !== '') {
            // Analyze the docblock's @param tags.
            $result = $this->docblockParser->parse($docBlock, [
                DocblockParser::PARAM_TYPE,
            ], '');

            if (isset($result['params']['$' . $varNode->name])) {
                return $result['params']['$' . $varNode->name]['type'];
            }
        }

        $isNullableInType = false;
        $isNullableByAssignment = false;
        $typeNode = $context->getNode()->type;

        if ($typeNode instanceof Node\NullableType) {
            $typeNode = $typeNode->type;
            $isNullableInType = true;
        } elseif ($context->getNode()->default instanceof Node\Expr\ConstFetch &&
            $context->getNode()->default->name->toString() === 'null'
        ) {
            $isNullableByAssignment = true;
        }

        if ($typeNode instanceof Node\Name) {
            $typeHintType = new IdentifierTypeNode(NodeHelpers::fetchClassName($typeNode));
        } elseif ($context->getNode()->type instanceof Node\Identifier) {
            $typeHintType = new IdentifierTypeNode($context->getNode()->type->name);
        } else {
            return new InvalidTypeNode();
        }

        /*
            ?Foo $foo           -> $foo has type Foo|null.
            Foo $foo = null     -> $foo has type Foo|null.
            ?Foo ...$foo        -> $foo has type array<Foo|null>.
            Foo ...$foo = null  -> $foo has type array<Foo>|null.
            ?Foo ...$foo = null -> $foo has type array<Foo|null>|null.
         */
        if ($isNullableInType) {
            $typeHintType = new UnionTypeNode([
                $typeHintType,
                new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::NULL_),
            ]);
        }

        if ($context->getNode()->variadic) {
            $typeHintType = new ArrayTypeNode($typeHintType);
        }

        // Extra check to ensure we don't apply nullability to twice in the case of "?Foo $foo = null'.
        if ($isNullableByAssignment && ($context->getNode()->variadic || !$isNullableInType)) {
            $typeHintType = new UnionTypeNode([
                $typeHintType,
                new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::NULL_),
            ]);
        }

        return $typeHintType;
    }

    /**
     * @return string|null
     */
    public function getFunctionDocblock(): ?string
    {
        return $this->functionDocblock;
    }

    /**
     * @todo Refactor. This is crap. Should use a factory or something.
     *
     * @param string|null $functionDocblock
     */
    public function setFunctionDocblock(?string $functionDocblock): void
    {
        $this->functionDocblock = $functionDocblock;
    }
}
