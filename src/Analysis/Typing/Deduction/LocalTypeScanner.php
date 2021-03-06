<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;

use Serenata\Analysis\Typing\TypeResolvingDocblockTypeTransformer;

use Serenata\Analysis\Visiting\ExpressionTypeInfo;
use Serenata\Analysis\Visiting\ExpressionTypeInfoMap;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Parsing;

use Serenata\Parsing\InvalidTypeNode;
use Serenata\Parsing\TypeNodeUnwrapper;
use Serenata\Parsing\DocblockTypeParserInterface;
use Serenata\Parsing\ToplevelTypeExtractorInterface;
use Serenata\Parsing\DocblockTypeTransformerInterface;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Scans for types affecting expressions (e.g. variables and properties) in a local scope in a file.
 *
 * This class can be used to scan for types that apply to an expression based on local rules, such as conditionals and
 * type overrides.
 */
final class LocalTypeScanner
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var ForeachNodeLoopValueTypeDeducer
     */
    private $foreachNodeLoopValueTypeDeducer;

    /**
     * @var FunctionLikeParameterTypeDeducer
     */
    private $functionLikeParameterTypeDeducer;

    /**
     * @var ExpressionLocalTypeAnalyzer
     */
    private $expressionLocalTypeAnalyzer;

    /**
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * @var ToplevelTypeExtractorInterface
     */
    private $toplevelTypeExtractor;

    /**
     * @var TypeResolvingDocblockTypeTransformer
     */
    private $typeResolvingDocblockTypeTransformer;

    /**
     * @var DocblockTypeTransformerInterface
     */
    private $docblockTypeTransformer;

    /**
     * @param NodeTypeDeducerInterface             $nodeTypeDeducer
     * @param ForeachNodeLoopValueTypeDeducer      $foreachNodeLoopValueTypeDeducer
     * @param FunctionLikeParameterTypeDeducer     $functionLikeParameterTypeDeducer
     * @param ExpressionLocalTypeAnalyzer          $expressionLocalTypeAnalyzer
     * @param DocblockTypeParserInterface          $docblockTypeParser
     * @param ToplevelTypeExtractorInterface       $toplevelTypeExtractor
     * @param TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer
     * @param DocblockTypeTransformerInterface     $docblockTypeTransformer
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ForeachNodeLoopValueTypeDeducer $foreachNodeLoopValueTypeDeducer,
        FunctionLikeParameterTypeDeducer $functionLikeParameterTypeDeducer,
        ExpressionLocalTypeAnalyzer $expressionLocalTypeAnalyzer,
        DocblockTypeParserInterface $docblockTypeParser,
        ToplevelTypeExtractorInterface $toplevelTypeExtractor,
        TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer,
        DocblockTypeTransformerInterface $docblockTypeTransformer
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->foreachNodeLoopValueTypeDeducer = $foreachNodeLoopValueTypeDeducer;
        $this->functionLikeParameterTypeDeducer = $functionLikeParameterTypeDeducer;
        $this->expressionLocalTypeAnalyzer = $expressionLocalTypeAnalyzer;
        $this->docblockTypeParser = $docblockTypeParser;
        $this->toplevelTypeExtractor = $toplevelTypeExtractor;
        $this->typeResolvingDocblockTypeTransformer = $typeResolvingDocblockTypeTransformer;
        $this->docblockTypeTransformer = $docblockTypeTransformer;
    }

    /**
     * Retrieves the types of a expression based on what's happening to it in a local scope.
     *
     * This can be used to deduce the type of local variables, class properties, ... that are influenced by local
     * assignments, if statements, ...
     *
     * @param TextDocumentItem $textDocumentItem
     * @param Position        $position
     * @param string          $expression
     * @param string[]        $defaultTypes
     *
     * @return TypeNode
     */
    public function getLocalExpressionTypes(
        TextDocumentItem $textDocumentItem,
        Position $position,
        string $expression,
        array $defaultTypes = []
    ): TypeNode {
        $expressionTypeInfoMap = $this->expressionLocalTypeAnalyzer->analyze($textDocumentItem, $position);

        if (!$expressionTypeInfoMap->has($expression)) {
            return new InvalidTypeNode();
        }

        return $this->getResolvedTypes(
            $expressionTypeInfoMap,
            $expression,
            $textDocumentItem,
            $position,
            $defaultTypes
        );
    }

    /**
     * Retrieves a list of fully resolved types for the variable.
     *
     * @param ExpressionTypeInfoMap $expressionTypeInfoMap
     * @param string                $expression
     * @param TextDocumentItem      $textDocumentItem
     * @param Position              $position
     * @param string[]              $defaultTypes
     *
     * @return TypeNode
     */
    private function getResolvedTypes(
        ExpressionTypeInfoMap $expressionTypeInfoMap,
        string $expression,
        TextDocumentItem $textDocumentItem,
        Position $position,
        array $defaultTypes = []
    ): TypeNode {
        $type = $this->getUnreferencedTypes(
            $expressionTypeInfoMap,
            $expression,
            $textDocumentItem,
            $position,
            $defaultTypes
        );

        $expressionTypeInfo = $expressionTypeInfoMap->get($expression);

        $resolvedTypes = [];

        $typeLine = $expressionTypeInfo->hasBestTypeOverrideMatch() ?
            $expressionTypeInfo->getBestTypeOverrideMatchLine() :
            $position->getLine();

        assert($typeLine !== null);

        $filePosition = new FilePosition($textDocumentItem->getUri(), new Position($typeLine, 0));

        return $this->typeResolvingDocblockTypeTransformer->resolve($type, $filePosition);
    }

    /**
     * Retrieves a list of types for the variable, with any referencing types (self, static, $this, ...)
     * resolved to their actual types.
     *
     * @param ExpressionTypeInfoMap $expressionTypeInfoMap
     * @param string                    $expression
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     * @param string[]                  $defaultTypes
     *
     * @return TypeNode
     */
    private function getUnreferencedTypes(
        ExpressionTypeInfoMap $expressionTypeInfoMap,
        string $expression,
        TextDocumentItem $textDocumentItem,
        Position $position,
        array $defaultTypes = []
    ): TypeNode {
        $expressionTypeInfo = $expressionTypeInfoMap->get($expression);

        $types = $this->getTypes($expressionTypeInfo, $expression, $textDocumentItem, $position, $defaultTypes);

        $unreferencedTypes = [];

        $selfType = $this->deduceTypesFromSelf($textDocumentItem, $position);
        $staticType = $this->deduceTypesFromStatic($textDocumentItem, $position);

        foreach ($types as $typeString) {
            $type = $this->docblockTypeParser->parse($typeString);

            $unreferencedTypes[] = $this->docblockTypeTransformer->transform($type, function (TypeNode $node) use ($selfType, $staticType): TypeNode {
                if ($node instanceof IdentifierTypeNode) {
                    if ($node->name === SpecialDocblockTypeIdentifierLiteral::SELF_) {
                        return new IdentifierTypeNode($selfType);
                    } elseif ($node->name === SpecialDocblockTypeIdentifierLiteral::STATIC_) {
                        return new IdentifierTypeNode($staticType);
                    }
                } elseif ($node instanceof ThisTypeNode) {
                    return new IdentifierTypeNode($staticType);
                }

                return $node;
            });
        }

        return TypeNodeUnwrapper::unwrap(new UnionTypeNode($unreferencedTypes));
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return TypeNode
     */
    private function deduceTypesFromSelf(TextDocumentItem $textDocumentItem, Position $position): TypeNode
    {
        $dummyNode = new Parsing\Node\Keyword\Self_();
        $dummyNode->setAttribute(
            'startFilePos',
            $position->getAsByteOffsetInString($textDocumentItem->getText(), PositionEncoding::VALUE)
        );

        return $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $dummyNode,
            $textDocumentItem,
            $position
        ));
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return TypeNode
     */
    private function deduceTypesFromStatic(TextDocumentItem $textDocumentItem, Position $position): TypeNode
    {
        $dummyNode = new Parsing\Node\Keyword\Static_();
        $dummyNode->setAttribute(
            'startFilePos',
            $position->getAsByteOffsetInString($textDocumentItem->getText(), PositionEncoding::VALUE)
        );

        return $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $dummyNode,
            $textDocumentItem,
            $position
        ));
    }

    /**
     * @param ExpressionTypeInfo $expressionTypeInfo
     * @param string             $expression
     * @param TextDocumentItem   $textDocumentItem
     * @param Position           $position
     * @param string[]           $defaultTypes
     *
     * @return string[]
     */
    private function getTypes(
        ExpressionTypeInfo $expressionTypeInfo,
        string $expression,
        TextDocumentItem $textDocumentItem,
        Position $position,
        array $defaultTypes = []
    ): array {
        if ($expressionTypeInfo->hasBestTypeOverrideMatch()) {
            assert($expressionTypeInfo->getBestTypeOverrideMatch() !== null);

            $type = $this->docblockTypeParser->parse($expressionTypeInfo->getBestTypeOverrideMatch());

            return array_map(function (TypeNode $nestedType): string {
                return (string) $nestedType;
            }, $this->toplevelTypeExtractor->extract($type));
        }

        $types = $defaultTypes;

        if ($expressionTypeInfo->hasBestMatch()) {
            assert($expressionTypeInfo->getBestMatch() !== null);

            $type = $this->getTypesForBestMatchNode(
                $expression,
                $expressionTypeInfo->getBestMatch(),
                $textDocumentItem,
                $position
            );

            $types = array_map(function (TypeNode $nestedType): string {
                return (string) $nestedType;
            }, $this->toplevelTypeExtractor->extract($type));
        }

        return $expressionTypeInfo->getTypePossibilityMap()->determineApplicableTypes($types);
    }

    /**
     * @param string           $expression
     * @param Node             $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return TypeNode
     */
    private function getTypesForBestMatchNode(
        string $expression,
        Node $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): TypeNode {
        if ($node instanceof Node\Stmt\Foreach_) {
            return $this->foreachNodeLoopValueTypeDeducer->deduce(new TypeDeductionContext(
                $node,
                $textDocumentItem,
                $position
            ));
        } elseif ($node instanceof Node\FunctionLike) {
            return $this->deduceTypesFromFunctionLikeParameter($node, $expression, $textDocumentItem, $position);
        }

        return $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $node,
            $textDocumentItem,
            $position
        ));
    }

    /**
     * @param Node\FunctionLike $node
     * @param string            $parameterName
     * @param TextDocumentItem  $textDocumentItem
     * @param Position          $position
     *
     * @return TypeNode
     */
    private function deduceTypesFromFunctionLikeParameter(
        Node\FunctionLike $node,
        string $parameterName,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): TypeNode {
        foreach ($node->getParams() as $param) {
            if ($param->var instanceof Node\Expr\Variable && $param->var->name === mb_substr($parameterName, 1)) {
                $this->functionLikeParameterTypeDeducer->setFunctionDocblock($node->getDocComment());

                return $this->functionLikeParameterTypeDeducer->deduce(new TypeDeductionContext(
                    $param,
                    $textDocumentItem,
                    $position
                ));
            }
        }

        return new InvalidTypeNode();
    }
}
