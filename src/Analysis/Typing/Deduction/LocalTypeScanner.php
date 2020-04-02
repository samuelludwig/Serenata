<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;

use PhpParser\Node;

use Serenata\Analysis\Typing\TypeAnalyzer;

use Serenata\Analysis\Visiting\ExpressionTypeInfo;
use Serenata\Analysis\Visiting\ExpressionTypeInfoMap;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Parsing;

use Serenata\Parsing\DocblockTypeParserInterface;

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
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFacotry;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

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
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFacotry
     * @param TypeAnalyzer                               $typeAnalyzer
     * @param NodeTypeDeducerInterface                   $nodeTypeDeducer
     * @param ForeachNodeLoopValueTypeDeducer            $foreachNodeLoopValueTypeDeducer
     * @param FunctionLikeParameterTypeDeducer           $functionLikeParameterTypeDeducer
     * @param ExpressionLocalTypeAnalyzer                $expressionLocalTypeAnalyzer
     * @param DocblockTypeParserInterface                $docblockTypeParser
     */
    public function __construct(
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFacotry,
        TypeAnalyzer $typeAnalyzer,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ForeachNodeLoopValueTypeDeducer $foreachNodeLoopValueTypeDeducer,
        FunctionLikeParameterTypeDeducer $functionLikeParameterTypeDeducer,
        ExpressionLocalTypeAnalyzer $expressionLocalTypeAnalyzer,
        DocblockTypeParserInterface $docblockTypeParser
    ) {
        $this->structureAwareNameResolverFacotry = $structureAwareNameResolverFacotry;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->foreachNodeLoopValueTypeDeducer = $foreachNodeLoopValueTypeDeducer;
        $this->functionLikeParameterTypeDeducer = $functionLikeParameterTypeDeducer;
        $this->expressionLocalTypeAnalyzer = $expressionLocalTypeAnalyzer;
        $this->docblockTypeParser = $docblockTypeParser;
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
     * @return string[]
     */
    public function getLocalExpressionTypes(
        TextDocumentItem $textDocumentItem,
        Position $position,
        string $expression,
        array $defaultTypes = []
    ): array {
        $expressionTypeInfoMap = $this->expressionLocalTypeAnalyzer->analyze($textDocumentItem, $position);

        if (!$expressionTypeInfoMap->has($expression)) {
            return [];
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
     * @return string[]
     */
    private function getResolvedTypes(
        ExpressionTypeInfoMap $expressionTypeInfoMap,
        string $expression,
        TextDocumentItem $textDocumentItem,
        Position $position,
        array $defaultTypes = []
    ): array {
        $types = $this->getUnreferencedTypes(
            $expressionTypeInfoMap,
            $expression,
            $textDocumentItem,
            $position,
            $defaultTypes
        );

        $expressionTypeInfo = $expressionTypeInfoMap->get($expression);

        $resolvedTypes = [];

        foreach ($types as $type) {
            $typeLine = $expressionTypeInfo->hasBestTypeOverrideMatch() ?
                $expressionTypeInfo->getBestTypeOverrideMatchLine() :
                $position->getLine();

            $filePosition = new FilePosition($textDocumentItem->getUri(), new Position($typeLine, 0));

            $resolvedTypes[] = $this->structureAwareNameResolverFacotry->create($filePosition)->resolve(
                $type,
                $filePosition
            );
        }

        return $resolvedTypes;
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
     * @return string[]
     */
    private function getUnreferencedTypes(
        ExpressionTypeInfoMap $expressionTypeInfoMap,
        string $expression,
        TextDocumentItem $textDocumentItem,
        Position $position,
        array $defaultTypes = []
    ): array {
        $expressionTypeInfo = $expressionTypeInfoMap->get($expression);

        $types = $this->getTypes($expressionTypeInfo, $expression, $textDocumentItem, $position, $defaultTypes);

        $unreferencedTypes = [];

        $selfType = $this->deduceTypesFromSelf($textDocumentItem, $position);
        $selfType = array_shift($selfType);
        $selfType = $selfType !== null ? $selfType : '';

        $staticType = $this->deduceTypesFromStatic($textDocumentItem, $position);
        $staticType = array_shift($staticType);
        $staticType = $staticType !== null ? $staticType : '';

        foreach ($types as $type) {
            $type = $this->typeAnalyzer->interchangeSelfWithActualType($type, $selfType);
            $type = $this->typeAnalyzer->interchangeStaticWithActualType($type, $staticType);
            $type = $this->typeAnalyzer->interchangeThisWithActualType($type, $staticType);

            $unreferencedTypes[] = $type;
        }

        return $unreferencedTypes;
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return string[]
     */
    private function deduceTypesFromSelf(TextDocumentItem $textDocumentItem, Position $position): array
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
     * @return array
     */
    private function deduceTypesFromStatic(TextDocumentItem $textDocumentItem, Position $position): array
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
            $type = $this->docblockTypeParser->parse($expressionTypeInfo->getBestTypeOverrideMatch());

            if ($type instanceof UnionTypeNode || $type instanceof IntersectionTypeNode) {
                return array_map(function (TypeNode $nestedType): string {
                    return (string) $nestedType;
                }, $type->types);
            }

            return [(string) $type];
        }

        $types = $defaultTypes;

        if ($expressionTypeInfo->hasBestMatch()) {
            $types = $this->getTypesForBestMatchNode(
                $expression,
                $expressionTypeInfo->getBestMatch(),
                $textDocumentItem,
                $position
            );
        }

        return $expressionTypeInfo->getTypePossibilityMap()->determineApplicableTypes($types);
    }

    /**
     * @param string           $expression
     * @param Node             $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return string[]
     */
    private function getTypesForBestMatchNode(
        string $expression,
        Node $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): array {
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
     * @return string[]
     */
    private function deduceTypesFromFunctionLikeParameter(
        Node\FunctionLike $node,
        string $parameterName,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): array {
        foreach ($node->getParams() as $param) {
            if ($param->var->name === mb_substr($parameterName, 1)) {
                $this->functionLikeParameterTypeDeducer->setFunctionDocblock($node->getDocComment());

                return $this->functionLikeParameterTypeDeducer->deduce(new TypeDeductionContext(
                    $param,
                    $textDocumentItem,
                    $position
                ));
            }
        }

        return [];
    }
}
