<?php

namespace PhpIntegrator\Analysis\Typing;

use UnexpectedValueException;

use PhpIntegrator\Parsing;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Conversion\ConstantConverter;
use PhpIntegrator\Analysis\Conversion\FunctionConverter;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Visiting\ExpressionTypeInfo;
use PhpIntegrator\Analysis\Visiting\TypeQueryingVisitor;
use PhpIntegrator\Analysis\Visiting\ScopeLimitingVisitor;
use PhpIntegrator\Analysis\Visiting\ExpressionTypeInfoMap;

use PhpIntegrator\Indexing\IndexDatabase;

use PhpIntegrator\Parsing\PartialParser;
use PhpIntegrator\Parsing\DocblockParser;

use PhpIntegrator\Utility\NodeHelpers;
use PhpIntegrator\Utility\SourceCodeHelpers;

use PhpParser\Node;
use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinterAbstract;

/**
 * Deduces the type(s) of an expression.
 */
class TypeDeducer
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var FileClassListProviderInterface
     */
    protected $fileClassListProvider;

    /**
     * @var DocblockParser
     */
    protected $docblockParser;

    /**
     * @var PartialParser
     */
    protected $partialParser;

    /**
     * @var TypeAnalyzer
     */
    protected $typeAnalyzer;

    /**
     * @var FileTypeResolverFactoryInterface
     */
    protected $fileTypeResolverFactory;

    /**
     * @var IndexDatabase
     */
    protected $indexDatabase;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @var FunctionConverter
     */
    protected $functionConverter;

    /**
     * @var ConstantConverter
     */
    protected $constantConverter;

    /**
     * @var PrettyPrinterAbstract
     */
    protected $prettyPrinter;

    /**
     * @param Parser                           $parser
     * @param FileClassListProviderInterface   $fileClassListProvider
     * @param DocblockParser                   $docblockParser
     * @param PartialParser                    $partialParser
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param IndexDatabase                    $indexDatabase
     * @param ClasslikeInfoBuilder             $classlikeInfoBuilder
     * @param FunctionConverter                $functionConverter
     * @param ConstantConverter                $constantConverter
     * @param PrettyPrinterAbstract            $prettyPrinter
     */
    public function __construct(
        Parser $parser,
        FileClassListProviderInterface $fileClassListProvider,
        DocblockParser $docblockParser,
        PartialParser $partialParser,
        TypeAnalyzer $typeAnalyzer,
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        IndexDatabase $indexDatabase,
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        FunctionConverter $functionConverter,
        ConstantConverter $constantConverter,
        PrettyPrinterAbstract $prettyPrinter
    ) {
        $this->parser = $parser;
        $this->fileClassListProvider = $fileClassListProvider;
        $this->docblockParser = $docblockParser;
        $this->partialParser = $partialParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->indexDatabase = $indexDatabase;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->functionConverter = $functionConverter;
        $this->constantConverter = $constantConverter;
        $this->prettyPrinter = $prettyPrinter;
    }

    /**
     * @param Node        $node
     * @param string|null $file
     * @param string      $code
     * @param int         $offset
     *
     * @return string[]
     */
    public function deduceTypesFromNode(Node $node, $file, $code, $offset)
    {
        if ($node instanceof Node\Expr\Variable) {
            return $this->deduceTypesFromVariableNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Scalar\LNumber) {
            return $this->deduceTypesFromLNumberNode($node);
        } elseif ($node instanceof Node\Scalar\DNumber) {
            return $this->deduceTypesFromDNumberNode($node);
        } elseif ($node instanceof Node\Scalar\String_) {
            return $this->deduceTypesFromStringNode($node);
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return $this->deduceTypesFromConstFetchNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Expr\ArrayDimFetch) {
            return $this->deduceTypesFromArrayDimFetchNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Expr\Closure) {
            return $this->deduceTypesFromClosureNode($node);
        } elseif ($node instanceof Node\Expr\New_) {
            return $this->deduceTypesFromNewNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Expr\Clone_) {
            return $this->deduceTypesFromCloneNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Expr\Array_) {
            return $this->deduceTypesFromArrayNode($node);
        } elseif ($node instanceof Parsing\Node\Keyword\Self_) {
            return $this->deduceNodesFromSelfNode($node, $file, $code, $offset);
        } elseif ($node instanceof Parsing\Node\Keyword\Static_) {
            return $this->deduceTypesFromStaticNode($node, $file, $code, $offset);
        } elseif ($node instanceof Parsing\Node\Keyword\Parent_) {
            return $this->deduceTypesFromParentNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Name) {
            return $this->deduceTypesFromNameNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Expr\FuncCall) {
            return $this->deduceTypesFromFuncCallNode($node);
        } elseif ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall) {
            return $this->deduceTypesFromMethodCallNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Expr\PropertyFetch || $node instanceof Node\Expr\StaticPropertyFetch) {
            return $this->deduceTypesFromPropertyFetch($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            return $this->deduceTypesFromClassConstFetchNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Expr\Assign) {
            return $this->deduceTypesFromAssignNode($node, $file, $code, $offset);
        } elseif ($node instanceof Node\Stmt\ClassLike) {
            return $this->deduceTypesFromClassLikeNode($node);
        }

        return [];
    }

    /**
     * @param Node\Expr\Variable $node
     * @param string|null        $file
     * @param string             $code
     * @param int                $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromVariableNode(Node\Expr\Variable $node, $file, $code, $offset)
    {
        if ($node->name instanceof Node\Expr) {
            return []; // Can't currently deduce type of a variable such as "$$this".
        }

        return $this->getLocalExpressionTypes($file, $code, '$' . $node->name, $offset);
    }

    /**
     * @param Node\Scalar\LNumber $node
     *
     * @return string[]
     */
    protected function deduceTypesFromLNumberNode(Node\Scalar\LNumber $node)
    {
        return ['int'];
    }

    /**
     * @param Node\Scalar\DNumber $node
     *
     * @return string[]
     */
    protected function deduceTypesFromDNumberNode(Node\Scalar\DNumber $node)
    {
        return ['float'];
    }

    /**
     * @param Node\Scalar\String_ $node
     *
     * @return string[]
     */
    protected function deduceTypesFromStringNode(Node\Scalar\String_ $node)
    {
        return ['string'];
    }

    /**
     * @param Node\Expr\ConstFetch $node
     * @param string|null          $file
     * @param string               $code
     * @param int                  $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromConstFetchNode(Node\Expr\ConstFetch $node, $file, $code, $offset)
    {
        $name = NodeHelpers::fetchClassName($node->name);

        if ($name === 'null') {
            return ['null'];
        } elseif ($name === 'true' || $name === 'false') {
            return ['bool'];
        }

        $line = SourceCodeHelpers::calculateLineByOffset($code, $offset);

        $fqcn = $this->fileTypeResolverFactory->create($file)->resolve($name, $line);

        $globalConstant = $this->indexDatabase->getGlobalConstantByFqcn($fqcn);

        if (!$globalConstant) {
            return [];
        }

        $convertedGlobalConstant = $this->constantConverter->convert($globalConstant);

        return $this->fetchResolvedTypesFromTypeArrays($convertedGlobalConstant['types']);
    }

    /**
     * @param Node\Expr\ArrayDimFetch $node
     * @param string|null             $file
     * @param string                  $code
     * @param int                     $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromArrayDimFetchNode(Node\Expr\ArrayDimFetch $node, $file, $code, $offset)
    {
        $types = $this->deduceTypesFromNode($node->var, $file, $code, $offset);

        $elementTypes = [];

        foreach ($types as $type) {
            if ($type === 'string') {
                $elementTypes[] = 'string';
            } elseif ($this->typeAnalyzer->isArraySyntaxTypeHint($type)) {
                $elementTypes[] = $this->typeAnalyzer->getValueTypeFromArraySyntaxTypeHint($type);
            } else {
                $elementTypes[] = 'mixed';
            }
        }

        return array_unique($elementTypes);
    }

    /**
     * @param Node\Expr\Closure $node
     *
     * @return string[]
     */
    protected function deduceTypesFromClosureNode(Node\Expr\Closure $node)
    {
        return ['\Closure'];
    }

    /**
     * @param Node\Expr\New_ $node
     * @param string|null    $file
     * @param string         $code
     * @param int            $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromNewNode(Node\Expr\New_ $node, $file, $code, $offset)
    {
        return $this->deduceTypesFromNode($node->class, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\Clone_ $node
     * @param string|null      $file
     * @param string           $code
     * @param int              $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromCloneNode(Node\Expr\Clone_ $node, $file, $code, $offset)
    {
        return $this->deduceTypesFromNode($node->expr, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\Array_ $node
     *
     * @return string[]
     */
    protected function deduceTypesFromArrayNode(Node\Expr\Array_ $node)
    {
        return ['array'];
    }

    /**
     * @param Parsing\Node\Keyword\Self_ $node
     * @param string|null                $file
     * @param string                     $code
     * @param int                        $offset
     *
     * @return string[]
     */
    protected function deduceNodesFromSelfNode(Parsing\Node\Keyword\Self_ $node, $file, $code, $offset)
    {
        return $this->deduceTypesFromNode(new Node\Name('self'), $file, $code, $offset);
    }

    /**
     * @param Parsing\Node\Keyword\Static_ $node
     * @param string|null                  $file
     * @param string                       $code
     * @param int                          $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromStaticNode(Parsing\Node\Keyword\Static_ $node, $file, $code, $offset)
    {
        return $this->deduceTypesFromNode(new Node\Name('static'), $file, $code, $offset);
    }

    /**
     * @param Parsing\Node\Keyword\Parent_ $node
     * @param string|null                  $file
     * @param string                       $code
     * @param int                          $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromParentNode(Parsing\Node\Keyword\Parent_ $node, $file, $code, $offset)
    {
        return $this->deduceTypesFromNode(new Node\Name('parent'), $file, $code, $offset);
    }

    /**
     * @param Node\Name   $node
     * @param string|null $file
     * @param string      $code
     * @param int         $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromNameNode(Node\Name $node, $file, $code, $offset)
    {
        $nameString = NodeHelpers::fetchClassName($node);

        if ($nameString === 'static' || $nameString === 'self') {
            $currentClass = $this->getCurrentClassAt($file, $code, $offset);

            return [$this->typeAnalyzer->getNormalizedFqcn($currentClass)];
        } elseif ($nameString === 'parent') {
            $currentClassName = $this->getCurrentClassAt($file, $code, $offset);

            if (!$currentClassName) {
                return [];
            }

            $classInfo = $this->classlikeInfoBuilder->getClasslikeInfo($currentClassName);

            if ($classInfo && !empty($classInfo['parents'])) {
                $type = $classInfo['parents'][0];

                return [$this->typeAnalyzer->getNormalizedFqcn($type)];
            }
        } else {
            $line = SourceCodeHelpers::calculateLineByOffset($code, $offset);

            $fqcn = $this->fileTypeResolverFactory->create($file)->resolve($nameString, $line);

            return [$fqcn];
        }
    }

    /**
     * @param Node\Expr\FuncCall $node
     *
     * @return string[]
     */
    protected function deduceTypesFromFuncCallNode(Node\Expr\FuncCall $node)
    {
        if ($node->name instanceof Node\Expr) {
            return []; // Can't currently deduce type of an expression such as "{$foo}()";
        }

        $name = NodeHelpers::fetchClassName($node->name);

        $globalFunction = $this->indexDatabase->getGlobalFunctionByFqcn($name);

        if (!$globalFunction) {
            return [];
        }

        $convertedGlobalFunction = $this->functionConverter->convert($globalFunction);

        return $this->fetchResolvedTypesFromTypeArrays($convertedGlobalFunction['returnTypes']);
    }

    /**
     * @param Node\Expr\MethodCall|Node\Expr\StaticCall $node
     * @param string|null                               $file
     * @param string                                    $code
     * @param int                                       $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromMethodCallNode(Node\Expr $node, $file, $code, $offset)
    {
        if ($node->name instanceof Node\Expr) {
            return []; // Can't currently deduce type of an expression such as "$this->{$foo}()";
        }

        $objectNode = ($node instanceof Node\Expr\MethodCall) ? $node->var : $node->class;
        $typesOfVar = $this->deduceTypesFromNode($objectNode, $file, $code, $offset);

        $types = [];

        foreach ($typesOfVar as $type) {
            $info = null;

            try {
                $info = $this->classlikeInfoBuilder->getClasslikeInfo($type);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            if (isset($info['methods'][$node->name])) {
                $fetchedTypes = $this->fetchResolvedTypesFromTypeArrays($info['methods'][$node->name]['returnTypes']);

                if (!empty($fetchedTypes)) {
                    $types += array_combine($fetchedTypes, array_fill(0, count($fetchedTypes), true));
                }
            }
        }

        // We use an associative array so we automatically avoid duplicate types.
        return array_keys($types);
    }

    /**
     * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $node
     * @param string|null                                           $file
     * @param string                                                $code
     * @param int                                                   $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromPropertyFetch(Node\Expr $node, $file, $code, $offset)
    {
        if ($node->name instanceof Node\Expr) {
            return []; // Can't currently deduce type of an expression such as "$this->{$foo}";
        }

        $expressionString = $this->prettyPrinter->prettyPrintExpr($node);

        $types = $this->getLocalExpressionTypes($file, $code, $expressionString, $offset);

        if (!empty($types)) {
            return $types;
        }

        $objectNode = null;

        if ($node instanceof Node\Expr\PropertyFetch) {
            $objectNode = $node->var;
        } else {
            $objectNode = $node->class;
        }

        $typesOfVar = $this->deduceTypesFromNode($objectNode, $file, $code, $offset);

        $types = [];

        foreach ($typesOfVar as $type) {
            $info = null;

            try {
                $info = $this->classlikeInfoBuilder->getClasslikeInfo($type);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            if (isset($info['properties'][$node->name])) {
                $fetchedTypes = $this->fetchResolvedTypesFromTypeArrays($info['properties'][$node->name]['types']);

                if (!empty($fetchedTypes)) {
                    $types += array_combine($fetchedTypes, array_fill(0, count($fetchedTypes), true));
                }
            }
        }

        // We use an associative array so we automatically avoid duplicate types.
        return array_keys($types);
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param string|null               $file
     * @param string                    $code
     * @param int                       $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromClassConstFetchNode(Node\Expr\ClassConstFetch $node, $file, $code, $offset)
    {
        $typesOfVar = $this->deduceTypesFromNode($node->class, $file, $code, $offset);

        $types = [];

        foreach ($typesOfVar as $type) {
            $info = null;

            try {
                $info = $this->classlikeInfoBuilder->getClasslikeInfo($type);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            if (isset($info['constants'][$node->name])) {
                $fetchedTypes = $this->fetchResolvedTypesFromTypeArrays($info['constants'][$node->name]['types']);

                if (!empty($fetchedTypes)) {
                    $types += array_combine($fetchedTypes, array_fill(0, count($fetchedTypes), true));
                }
            }
        }

        // We use an associative array so we automatically avoid duplicate types.
        return array_keys($types);
    }

    /**
     * @param Node\Expr\Assign $node
     * @param string|null      $file
     * @param string           $code
     * @param int              $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromAssignNode(Node\Expr\Assign $node, $file, $code, $offset)
    {
        if ($node->expr instanceof Node\Expr\Ternary) {
            $firstOperandType = $this->deduceTypesFromNode(
                $node->expr->if ?: $node->expr->cond,
                $file,
                $code,
                $node->getAttribute('startFilePos')
            );

            $secondOperandType = $this->deduceTypesFromNode(
                $node->expr->else,
                $file,
                $code,
                $node->getAttribute('startFilePos')
            );

            return array_unique(array_merge($firstOperandType, $secondOperandType));
        }

        return $this->deduceTypesFromNode($node->expr, $file, $code, $node->getAttribute('startFilePos'));
    }

    /**
     * @param Node\Stmt\ClassLike $node
     */
    protected function deduceTypesFromClassLikeNode(Node\Stmt\ClassLike $node)
    {
        return [(string) $node->name];
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
        $types = $this->deduceTypesFromNode($node->expr, $file, $code, $node->getAttribute('startFilePos'));

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

                if ($param->type instanceof Node\Name) {
                    $typeHintType = NodeHelpers::fetchClassName($param->type);

                    if ($param->variadic) {
                        $typeHintType .= '[]';
                    }

                    return [$typeHintType];
                }

                return $param->type ? [$param->type] : [];
            }
        }

        return [];
    }

    /**
     * @param Node\Stmt\Catch_ $node
     * @param string           $parameterName
     * @param string           $file
     * @param string           $code
     * @param int              $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromCatchParameter(Node\Stmt\Catch_ $node, $parameterName, $file, $code, $offset)
    {
        $types = array_map(function (Node\Name $name) use ($file, $code, $offset) {
            return $this->deduceTypesFromNode($name, $file, $code, $offset);
        }, $node->types);

        $types = array_reduce($types, function (array $subTypes, $carry) {
            return array_merge($carry, $subTypes);
        }, []);

        return $types;
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
        } catch (Error $e) {
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
     * Retrieves the types of a expression based on what's happening to it in a local scope.
     *
     * This can be used to deduce the type of local variables, class properties, ... that are influenced by local
     * assignments, if statements, ...
     *
     * @param string     $file
     * @param string     $code
     * @param string     $expression
     * @param int        $offset
     *
     * @return string[]
     */
    protected function getLocalExpressionTypes($file, $code, $expression, $offset)
    {
        $typeQueryingVisitor = $this->walkTypeQueryingVisitorTo($code, $offset);

        $expressionTypeInfoMap = $typeQueryingVisitor->getExpressionTypeInfoMap();
        $offsetLine = SourceCodeHelpers::calculateLineByOffset($code, $offset);

        if (!$expressionTypeInfoMap->has($expression)) {
            return [];
        }

        return $this->getResolvedTypes($expressionTypeInfoMap, $expression, $file, $offsetLine, $code, $offset);
    }

    /**
     * @param ExpressionTypeInfo $expressionTypeInfo
     * @param string             $expression
     * @param string             $file
     * @param string             $code
     * @param int                $offset
     *
     * @return string[]
     */
    protected function getTypes(ExpressionTypeInfo $expressionTypeInfo, $expression, $file, $code, $offset)
    {
        if ($expressionTypeInfo->hasBestTypeOverrideMatch()) {
            return $this->typeAnalyzer->getTypesForTypeSpecification($expressionTypeInfo->getBestTypeOverrideMatch());
        }

        $types = [];

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
        } elseif ($node instanceof Node\Stmt\Catch_) {
            return $this->deduceTypesFromCatchParameter($node, $expression, $file, $code, $offset);
        }

        return $this->deduceTypesFromNode($node, $file, $code, $offset);
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
     *
     * @return string[]
     */
    protected function getUnreferencedTypes(
        ExpressionTypeInfoMap $expressionTypeInfoMap,
        $expression,
        $file,
        $code,
        $offset
    ) {
        $expressionTypeInfo = $expressionTypeInfoMap->get($expression);

        $types = $this->getTypes($expressionTypeInfo, $expression, $file, $code, $offset);

        $unreferencedTypes = [];

        foreach ($types as $type) {
            if (in_array($type, ['self', 'static', '$this'], true)) {
                $unreferencedTypes = array_merge(
                    $unreferencedTypes,
                    $this->getUnreferencedTypes($expressionTypeInfoMap, '$this', $file, $code, $offset)
                );
            } else {
                $unreferencedTypes[] = $type;
            }
        }

        return $unreferencedTypes;
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
     *
     * @return string[]
     */
    protected function getResolvedTypes(
        ExpressionTypeInfoMap $expressionTypeInfoMap,
        $expression,
        $file,
        $line,
        $code,
        $offset
    ) {
        $types = $this->getUnreferencedTypes($expressionTypeInfoMap, $expression, $file, $code, $offset);

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
     * @param array $typeArray
     *
     * @return string
     */
    protected function fetchResolvedTypeFromTypeArray(array $typeArray)
    {
        return $typeArray['resolvedType'];
    }

    /**
     * @param array $typeArrays
     *
     * @return string[]
     */
    protected function fetchResolvedTypesFromTypeArrays(array $typeArrays)
    {
        return array_map([$this, 'fetchResolvedTypeFromTypeArray'], $typeArrays);
    }

    /**
     * @param string $file
     * @param string $source
     * @param int    $offset
     *
     * @return string|null
     */
    protected function getCurrentClassAt($file, $source, $offset)
    {
        $line = SourceCodeHelpers::calculateLineByOffset($source, $offset);

        return $this->getCurrentClassAtLine($file, $source, $line);
    }

    /**
     * @param string $file
     * @param string $source
     * @param int    $line
     *
     * @return string|null
     */
    protected function getCurrentClassAtLine($file, $source, $line)
    {
        $classes = $this->fileClassListProvider->getClassListForFile($file);

        foreach ($classes as $fqcn => $class) {
            if ($line >= $class['startLine'] && $line <= $class['endLine']) {
                return $fqcn;
            }
        }

        return null;
    }
}
