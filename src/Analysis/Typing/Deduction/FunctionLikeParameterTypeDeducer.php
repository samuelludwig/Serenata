<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;

use PhpParser\Node;

use Serenata\Parsing\DocblockParser;

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
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Param) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $varNode = $context->getNode()->var;

        if ($varNode instanceof Node\Expr\Error) {
            return [];
        } elseif ($varNode->name instanceof Node\Expr) {
            return [];
        }

        $docBlock = $this->getFunctionDocblock();

        if ($docBlock !== null && $docBlock !== '') {
            // Analyze the docblock's @param tags.
            $result = $this->docblockParser->parse($docBlock, [
                DocblockParser::PARAM_TYPE,
            ], '');

            if (isset($result['params']['$' . $varNode->name])) {
                $type = $result['params']['$' . $varNode->name]['type'];

                if ($type instanceof UnionTypeNode || $type instanceof IntersectionTypeNode) {
                    return array_map(function (TypeNode $nestedType): string {
                        return (string) $nestedType;
                    }, $type->types);
                }

                return [(string) $type];
            }
        }

        $isNullable = false;
        $typeNode = $context->getNode()->type;

        if ($typeNode instanceof Node\NullableType) {
            $typeNode = $typeNode->type;
            $isNullable = true;
        } elseif ($context->getNode()->default instanceof Node\Expr\ConstFetch &&
            $context->getNode()->default->name->toString() === 'null'
        ) {
            $isNullable = true;
        }

        if ($typeNode instanceof Node\Name) {
            $typeHintType = NodeHelpers::fetchClassName($typeNode);

            if ($context->getNode()->variadic) {
                $typeHintType .= '[]';
            }

            return $isNullable ? [$typeHintType, 'null'] : [$typeHintType];
        } elseif ($context->getNode()->type instanceof Node\Identifier) {
            return [$context->getNode()->type->name];
        }

        return [];
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
