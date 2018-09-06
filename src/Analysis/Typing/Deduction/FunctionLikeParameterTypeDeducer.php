<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Analysis\Typing\TypeAnalyzer;

use Serenata\Parsing\DocblockParser;

use Serenata\Utility\NodeHelpers;

/**
 * Type deducer that can deduce the type of a parameter of a {@see Node\FunctionLike} node.
 */
final class FunctionLikeParameterTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var string|null
     */
    private $functionDocblock;

    /**
     * @param TypeAnalyzer   $typeAnalyzer
     * @param DocblockParser $docblockParser
     */
    public function __construct(TypeAnalyzer $typeAnalyzer, DocblockParser $docblockParser)
    {
        $this->typeAnalyzer = $typeAnalyzer;
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

        if ($docBlock = $this->getFunctionDocblock()) {
            // Analyze the docblock's @param tags.
            $result = $this->docblockParser->parse($docBlock, [
                DocblockParser::PARAM_TYPE,
            ], '');

            if (isset($result['params']['$' . $varNode->name])) {
                return $this->typeAnalyzer->getTypesForTypeSpecification(
                    $result['params']['$' . $varNode->name]['type']
                );
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
     * @param string|null $functionDocblock
     */
    public function setFunctionDocblock(?string $functionDocblock): void
    {
        $this->functionDocblock = $functionDocblock;
    }
}
