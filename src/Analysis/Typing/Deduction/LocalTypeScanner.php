<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpIntegrator\Parsing;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\FileTypeResolverFactoryInterface;

use PhpIntegrator\Analysis\Visiting\ExpressionTypeInfo;
use PhpIntegrator\Analysis\Visiting\TypeQueryingVisitor;
use PhpIntegrator\Analysis\Visiting\ScopeLimitingVisitor;
use PhpIntegrator\Analysis\Visiting\ExpressionTypeInfoMap;

use PhpIntegrator\Parsing\DocblockParser;

use PhpIntegrator\Utility\NodeHelpers;
use PhpIntegrator\Utility\SourceCodeHelpers;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinterAbstract;

/**
 * Scans for types affecting expressions (e.g. variables and properties) in a local scope in a file.
 *
 * This class can be used to scan for types that apply to an expression based on local rules, such as conditionals and
 * type overrides.
 */
class LocalTypeScanner
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var DocblockParser
     */
    protected $docblockParser;

    /**
     * @var PrettyPrinterAbstract
     */
    protected $prettyPrinter;

    /**
     * @var FileTypeResolverFactoryInterface
     */
    protected $fileTypeResolverFactory;

    /**
     * @var TypeAnalyzer
     */
    protected $typeAnalyzer;

    /**
     * @var NodeTypeDeducerInterface
     */
    protected $nodeTypeDeducer;

    /**
     * @param Parser                           $parser
     * @param DocblockParser                   $docblockParser
     * @param PrettyPrinterAbstract            $prettyPrinter
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param NodeTypeDeducerInterface         $nodeTypeDeducer
     */
    public function __construct(
        Parser $parser,
        DocblockParser $docblockParser,
        PrettyPrinterAbstract $prettyPrinter,
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        TypeAnalyzer $typeAnalyzer,
        NodeTypeDeducerInterface $nodeTypeDeducer
    ) {
        $this->parser = $parser;
        $this->docblockParser = $docblockParser;
        $this->prettyPrinter = $prettyPrinter;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
    }

    /**
     * Retrieves the types of a expression based on what's happening to it in a local scope.
     *
     * This can be used to deduce the type of local variables, class properties, ... that are influenced by local
     * assignments, if statements, ...
     *
     * @param string     $file
     * @param string     $code
     * @param string     $expression
     * @param int        $offset
     * @param string[]   $defaultTypes
     *
     * @return string[]
     */
    public function getLocalExpressionTypes($file, $code, $expression, $offset, $defaultTypes = [])
    {
        $typeQueryingVisitor = $this->walkTypeQueryingVisitorTo($code, $offset);

        $expressionTypeInfoMap = $typeQueryingVisitor->getExpressionTypeInfoMap();
        $offsetLine = SourceCodeHelpers::calculateLineByOffset($code, $offset);

        if (!$expressionTypeInfoMap->has($expression)) {
            return [];
        }

        return $this->getResolvedTypes(
            $expressionTypeInfoMap,
            $expression,
            $file,
            $offsetLine,
            $code,
            $offset,
            $defaultTypes
        );
    }

    /**
     * @param string $code
     * @param int    $offset
     *
     * @throws UnexpectedValueException
     *
     * @return TypeQueryingVisitor
     */
    protected function walkTypeQueryingVisitorTo($code, $offset)
    {
        $nodes = null;

        $handler = new ErrorHandler\Collecting();

        try {
            $nodes = $this->parser->parse($code, $handler);
        } catch (\PhpParser\Error $e) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        // In php-parser 2.x, this happens when you enter $this-> before an if-statement, because of a syntax error that
        // it can not recover from.
        if ($nodes === null) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        $scopeLimitingVisitor = new ScopeLimitingVisitor($offset);
        $typeQueryingVisitor = new TypeQueryingVisitor($this->docblockParser, $this->prettyPrinter, $offset);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($scopeLimitingVisitor);
        $traverser->addVisitor($typeQueryingVisitor);
        $traverser->traverse($nodes);

        return $typeQueryingVisitor;
    }

    /**
     * Retrieves a list of fully resolved types for the variable.
     *
     * @param ExpressionTypeInfoMap $expressionTypeInfoMap
     * @param string                $expression
     * @param string                $file
     * @param int                   $line
     * @param string                $code
     * @param int                   $offset
     * @param string[]              $defaultTypes
     *
     * @return string[]
     */
    protected function getResolvedTypes(
        ExpressionTypeInfoMap $expressionTypeInfoMap,
        $expression,
        $file,
        $line,
        $code,
        $offset,
        $defaultTypes = []
    ) {
        $types = $this->getUnreferencedTypes($expressionTypeInfoMap, $expression, $file, $code, $offset, $defaultTypes);

        $expressionTypeInfo = $expressionTypeInfoMap->get($expression);

        $resolvedTypes = [];

        foreach ($types as $type) {
            $typeLine = $expressionTypeInfo->hasBestTypeOverrideMatch() ?
                $expressionTypeInfo->getBestTypeOverrideMatchLine() :
                $line;

            $resolvedTypes[] = $this->fileTypeResolverFactory->create($file)->resolve($type, $typeLine);
        }

        return $resolvedTypes;
    }

    /**
     * Retrieves a list of types for the variable, with any referencing types (self, static, $this, ...)
     * resolved to their actual types.
     *
     * @param ExpressionTypeInfoMap $expressionTypeInfoMap
     * @param string                $expression
     * @param string                $file
     * @param string                $code
     * @param int                   $offset
     * @param string[]              $defaultTypes
     *
     * @return string[]
     */
    protected function getUnreferencedTypes(
        ExpressionTypeInfoMap $expressionTypeInfoMap,
        $expression,
        $file,
        $code,
        $offset,
        $defaultTypes = []
    ) {
        $expressionTypeInfo = $expressionTypeInfoMap->get($expression);

        $types = $this->getTypes($expressionTypeInfo, $expression, $file, $code, $offset, $defaultTypes);

        $unreferencedTypes = [];

        $selfType = $this->deduceTypesFromSelf($file, $code, $offset);
        $selfType = array_shift($selfType);
        $selfType = $selfType ?: '';

        $staticType = $this->deduceTypesFromStatic($file, $code, $offset);
        $staticType = array_shift($staticType);
        $staticType = $staticType ?: '';

        foreach ($types as $type) {
            $type = $this->typeAnalyzer->interchangeSelfWithActualType($type, $selfType);
            $type = $this->typeAnalyzer->interchangeStaticWithActualType($type, $staticType);
            $type = $this->typeAnalyzer->interchangeThisWithActualType($type, $staticType);

            $unreferencedTypes[] = $type;
        }

        return $unreferencedTypes;
    }

    /**
     * @param string $file
     * @param string $code
     * @param int    $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromSelf($file, $code, $offset)
    {
        $dummyNode = new Parsing\Node\Keyword\Self_();

        return $this->nodeTypeDeducer->deduce($dummyNode, $file, $code, $offset);
    }

    /**
     * @param string $file
     * @param string $code
     * @param int    $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromStatic($file, $code, $offset)
    {
        $dummyNode = new Parsing\Node\Keyword\Static_();

        return $this->nodeTypeDeducer->deduce($dummyNode, $file, $code, $offset);
    }

    /**
     * @param ExpressionTypeInfo $expressionTypeInfo
     * @param string             $expression
     * @param string             $file
     * @param string             $code
     * @param int                $offset
     * @param string[]           $defaultTypes
     *
     * @return string[]
     */
    protected function getTypes(
        ExpressionTypeInfo $expressionTypeInfo,
        $expression,
        $file,
        $code,
        $offset,
        $defaultTypes = []
    ) {
        if ($expressionTypeInfo->hasBestTypeOverrideMatch()) {
            return $this->typeAnalyzer->getTypesForTypeSpecification($expressionTypeInfo->getBestTypeOverrideMatch());
        }

        $types = $defaultTypes;

        if ($expressionTypeInfo->hasBestMatch()) {
            $types = $this->getTypesForBestMatchNode($expression, $expressionTypeInfo->getBestMatch(), $file, $code, $offset);
        }

        return $expressionTypeInfo->getTypePossibilityMap()->determineApplicableTypes($types);
    }

    /**
     * @param string $expression
     * @param Node   $node
     * @param string $file
     * @param string $code
     * @param int    $offset
     *
     * @return string[]
     */
    protected function getTypesForBestMatchNode($expression, Node $node, $file, $code, $offset)
    {
        if ($node instanceof Node\Stmt\Foreach_) {
            return $this->deduceTypesFromLoopValueInForeachNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\FunctionLike) {
            return $this->deduceTypesFromFunctionLikeParameter($node, $expression);
        }

        return $this->nodeTypeDeducer->deduce($node, $file, $code, $offset);
    }

    /**
     * @param Node\Stmt\Foreach_ $node
     * @param string|null        $file
     * @param string             $code
     * @param int                $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromLoopValueInForeachNode(Node\Stmt\Foreach_ $node, $file, $code, $offset)
    {
        $types = $this->nodeTypeDeducer->deduce($node->expr, $file, $code, $node->getAttribute('startFilePos'));

        foreach ($types as $type) {
            if ($this->typeAnalyzer->isArraySyntaxTypeHint($type)) {
                return [$this->typeAnalyzer->getValueTypeFromArraySyntaxTypeHint($type)];
            }
        }

        return [];
    }

    /**
     * @param Node\FunctionLike $node
     * @param string            $parameterName
     *
     * @return string[]
     */
    protected function deduceTypesFromFunctionLikeParameter(Node\FunctionLike $node, $parameterName)
    {
        foreach ($node->getParams() as $param) {
            if ($param->name === mb_substr($parameterName, 1)) {
                if ($docBlock = $node->getDocComment()) {
                    // Analyze the docblock's @param tags.
                    $name = null;

                    if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
                        $name = $node->name;
                    }

                    $result = $this->docblockParser->parse((string) $docBlock, [
                        DocblockParser::PARAM_TYPE
                    ], $name, true);

                    if (isset($result['params'][$parameterName])) {
                        return $this->typeAnalyzer->getTypesForTypeSpecification(
                            $result['params'][$parameterName]['type']
                        );
                    }
                }

                // TODO: Support NullableType (PHP 7.1).
                if ($param->type instanceof Node\Name) {
                    $typeHintType = NodeHelpers::fetchClassName($param->type);

                    if ($param->variadic) {
                        $typeHintType .= '[]';
                    }

                    return [$typeHintType];
                } elseif (is_string($param->type)) {
                    return [$param->type];
                }

                return [];
            }
        }

        return [];
    }
}
