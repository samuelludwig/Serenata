<?php

namespace PhpIntegrator\Analysis\Typing;

use UnexpectedValueException;

use PhpIntegrator\Parsing;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Conversion\ConstantConverter;
use PhpIntegrator\Analysis\Conversion\FunctionConverter;

use PhpIntegrator\Analysis\Typing\TypeResolver;
use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Visiting\TypePossibility;
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
     * @var TypeResolver
     */
    protected $typeResolver;

    /**
     * @var FileTypeResolverFactoryInterface
     */
    protected $fileTypeResolverFactory;

    /**
     * @var TypeQueryingVisitor
     */
    protected $typeQueryingVisitor;

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
     * @param TypeResolver                     $typeResolver
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
        TypeResolver $typeResolver,
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
        $this->typeResolver = $typeResolver;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->indexDatabase = $indexDatabase;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->functionConverter = $functionConverter;
        $this->constantConverter = $constantConverter;
        $this->prettyPrinter = $prettyPrinter;
    }

    /**
     * @param string|null $file
     * @param string      $code
     * @param Node        $node
     * @param int         $offset
     *
     * @return string[]
     */
    public function deduceTypesFromNode($file, $code, Node $node, $offset)
    {
        if ($node instanceof Node\Expr\Variable) {
            if ($node->name instanceof Node\Expr) {
                return []; // Can't currently deduce type of a variable such as "$$this".
            }

            return $this->getLocalExpressionTypes($file, $code, '$' . $node->name, $offset);
        } elseif ($node instanceof Node\Scalar\LNumber) {
            return ['int'];
        } elseif ($node instanceof Node\Scalar\DNumber) {
            return ['float'];
        } elseif ($node instanceof Node\Scalar\String_) {
            return ['string'];
        } elseif ($node instanceof Node\Expr\ConstFetch) {
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
        } elseif ($node instanceof Node\Expr\Closure) {
            return ['\Closure'];
        } elseif ($node instanceof Node\Expr\New_) {
            return $this->deduceTypesFromNode($file, $code, $node->class, $offset);
        } elseif ($node instanceof Node\Expr\Clone_) {
            return $this->deduceTypesFromNode($file, $code, $node->expr, $offset);
        } elseif ($node instanceof Node\Expr\Array_) {
            return ['array'];
        } elseif ($node instanceof Parsing\Node\Keyword\Self_) {
            return $this->deduceTypesFromNode($file, $code, new Node\Name('self'), $offset);
        } elseif ($node instanceof Parsing\Node\Keyword\Static_) {
            return $this->deduceTypesFromNode($file, $code, new Node\Name('static'), $offset);
        } elseif ($node instanceof Parsing\Node\Keyword\Parent_) {
            return $this->deduceTypesFromNode($file, $code, new Node\Name('parent'), $offset);
        } elseif ($node instanceof Node\Name) {
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
        } elseif ($node instanceof Node\Expr\FuncCall) {
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
        } elseif ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall) {
            if ($node->name instanceof Node\Expr) {
                return []; // Can't currently deduce type of an expression such as "$this->{$foo}()";
            }

            $objectNode = ($node instanceof Node\Expr\MethodCall) ? $node->var : $node->class;

            $typesOfVar = $this->deduceTypesFromNode($file, $code, $objectNode, $offset);

            $types = [];

            foreach ($typesOfVar as $type) {
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
            $types = array_keys($types);

            return $types;
        } elseif ($node instanceof Node\Expr\PropertyFetch || $node instanceof Node\Expr\StaticPropertyFetch) {
            if ($node->name instanceof Node\Expr) {
                return []; // Can't currently deduce type of an expression such as "$this->{$foo}";
            }

            $objectNode = null;

            if ($node instanceof Node\Expr\PropertyFetch) {
                $expressionString = $this->prettyPrinter->prettyPrintExpr($node);

                $types = $this->getLocalExpressionTypes($file, $code, $expressionString, $offset);

                if (!empty($types)) {
                    return $types;
                }

                $objectNode = $node->var;
            } else {
                $objectNode = $node->class;
            }

            $typesOfVar = $this->deduceTypesFromNode($file, $code, $objectNode, $offset);

            $types = [];

            foreach ($typesOfVar as $type) {
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
            $types = array_keys($types);

            return $types;
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            $typesOfVar = $this->deduceTypesFromNode($file, $code, $node->class, $offset);

            $types = [];

            foreach ($typesOfVar as $type) {
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
            $types = array_keys($types);

            return $types;
        }

        return [];

        // TODO: getTypesForNode can be (partially) merged into this method.
        // TODO: Refactor (extract methods such as deduceTypesFromMethodCall, deduceTypesFromPropertyFetch, ...).
    }

    /**
     * @param string $code
     * @param int    $offset
     *
     * @throws UnexpectedValueException
     */
    protected function walkTypeQueryingVisitorTo($code, $offset)
    {
        $nodes = null;

        try {
            $nodes = $this->parser->parse($code);
        } catch (Error $e) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        // In php-parser 2.x, this happens when you enter $this-> before an if-statement, because of a syntax error that
        // it can not recover from.
        if ($nodes === null) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        $scopeLimitingVisitor = new ScopeLimitingVisitor($offset);
        $this->typeQueryingVisitor = new TypeQueryingVisitor($this->docblockParser, $this->prettyPrinter, $offset);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($scopeLimitingVisitor);
        $traverser->addVisitor($this->typeQueryingVisitor);
        $traverser->traverse($nodes);
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
        $this->walkTypeQueryingVisitorTo($code, $offset);

        $expressionTypeInfoMap = $this->typeQueryingVisitor->getExpressionTypeInfoMap();
        $offsetLine = SourceCodeHelpers::calculateLineByOffset($code, $offset);

        if (!$expressionTypeInfoMap->has($expression)) {
            return [];
        }

        return $this->getResolvedTypes($expressionTypeInfoMap, $expression, $file, $offsetLine, $code);
    }

    /**
     * @param string $variable
     * @param Node   $node
     * @param string $file
     * @param string $code
     *
     * @return string[]
     */
    protected function getTypesForNode($variable, Node $node, $file, $code)
    {
        if ($node instanceof Node\Expr\Assign) {
            if ($node->expr instanceof Node\Expr\Ternary) {
                $firstOperandType = $this->deduceTypesFromNode(
                    $file,
                    $code,
                    $node->expr->if ?: $node->expr->cond,
                    $node->getAttribute('startFilePos')
                );

                $secondOperandType = $this->deduceTypesFromNode(
                    $file,
                    $code,
                    $node->expr->else,
                    $node->getAttribute('startFilePos')
                );

                return array_unique(array_merge($firstOperandType, $secondOperandType));
            } else {
                return $this->deduceTypesFromNode(
                    $file,
                    $code,
                    $node->expr,
                    $node->getAttribute('startFilePos')
                );
            }
        } elseif ($node instanceof Node\Stmt\Foreach_) {
            $types = $this->deduceTypesFromNode(
                $file,
                $code,
                $node->expr,
                $node->getAttribute('startFilePos')
            );

            foreach ($types as $type) {
                if ($type && mb_strpos($type, '[]') !== false) {
                    $type = mb_substr($type, 0, -2);

                    return $type ? [$type] : [];
                }
            }
        } elseif ($node instanceof Node\FunctionLike) {
            foreach ($node->getParams() as $param) {
                if ($param->name === mb_substr($variable, 1)) {
                    if ($docBlock = $node->getDocComment()) {
                        // Analyze the docblock's @param tags.
                        $name = null;

                        if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
                            $name = $node->name;
                        }

                        $result = $this->docblockParser->parse((string) $docBlock, [
                            DocblockParser::PARAM_TYPE
                        ], $name, true);

                        if (isset($result['params'][$variable])) {
                            return $this->typeAnalyzer->getTypesForTypeSpecification(
                                $result['params'][$variable]['type']
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
        } elseif ($node instanceof Node\Stmt\ClassLike) {
            return [(string) $node->name];
        } elseif ($node instanceof Node\Name) {
            return [NodeHelpers::fetchClassName($node)];
        }

        return [];
    }

    /**
     * @param ExpressionTypeInfo $expressionTypeInfo
     * @param string             $expression
     * @param string             $file
     * @param string             $code
     *
     * @return string[]
     */
    protected function getTypes(ExpressionTypeInfo $expressionTypeInfo, $expression, $file, $code)
    {
        if ($expressionTypeInfo->hasBestTypeOverrideMatch()) {
            return $this->typeAnalyzer->getTypesForTypeSpecification($expressionTypeInfo->getBestTypeOverrideMatch());
        }

        $guaranteedTypes = [];
        $possibleTypeMap = [];

        $typePossibilities = $expressionTypeInfo->getTypePossibilities();

        foreach ($typePossibilities as $type => $possibility) {
            if ($possibility === TypePossibility::TYPE_GUARANTEED) {
                $guaranteedTypes[] = $type;
            } elseif ($possibility === TypePossibility::TYPE_POSSIBLE) {
                $possibleTypeMap[$type] = true;
            }
        }

        $types = [];

        // Types guaranteed by a conditional statement take precedence (if they didn't apply, the if statement could
        // never have executed in the first place).
        if (!empty($guaranteedTypes)) {
            $types = $guaranteedTypes;
        } elseif ($expressionTypeInfo->hasBestMatch()) {
            $types = $this->getTypesForNode($expression, $expressionTypeInfo->getBestMatch(), $file, $code);
        }

        $filteredTypes = [];

        foreach ($types as $type) {
            if (isset($typePossibilities[$type])) {
                $possibility = $typePossibilities[$type];

                if ($possibility === TypePossibility::TYPE_IMPOSSIBLE) {
                    continue;
                } elseif (isset($possibleTypeMap[$type])) {
                    $filteredTypes[] = $type;
                } elseif ($possibility === TypePossibility::TYPE_GUARANTEED) {
                    $filteredTypes[] = $type;
                }
            } elseif (empty($possibleTypeMap)) {
                // If the possibleTypeMap wasn't empty, the types the variable can have are limited to those present
                // in it (it acts as a whitelist).
                $filteredTypes[] = $type;
            }
        }

        return $filteredTypes;
    }

    /**
     * Retrieves a list of types for the variable, with any referencing types (self, static, $this, ...)
     * resolved to their actual types.
     *
     * @param ExpressionTypeInfoMap $expressionTypeInfoMap
     * @param string                $expression
     * @param string                $file
     * @param string                $code
     *
     * @return string[]
     */
    protected function getUnreferencedTypes(ExpressionTypeInfoMap $expressionTypeInfoMap, $expression, $file, $code)
    {
        $expressionTypeInfo = $expressionTypeInfoMap->get($expression);

        $types = $this->getTypes($expressionTypeInfo, $expression, $file, $code);

        $unreferencedTypes = [];

        foreach ($types as $type) {
            if (in_array($type, ['self', 'static', '$this'], true)) {
                $unreferencedTypes = array_merge(
                    $unreferencedTypes,
                    $this->getUnreferencedTypes($expressionTypeInfoMap, '$this', $file, $code)
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
     *
     * @return string[]
     */
    protected function getResolvedTypes(ExpressionTypeInfoMap $expressionTypeInfoMap, $expression, $file, $line, $code)
    {
        $types = $this->getUnreferencedTypes($expressionTypeInfoMap, $expression, $file, $code);

        $expressionTypeInfo = $expressionTypeInfoMap->get($expression);

        $resolvedTypes = [];

        foreach ($types as $type) {
            $isArraySyntaxTypeHint = $this->typeAnalyzer->isArraySyntaxTypeHint($type);

            if ($isArraySyntaxTypeHint) {
                $type = mb_substr($type, 0, -2);
            }

            if ($this->typeAnalyzer->isClassType($type)) {
                $typeLine = $expressionTypeInfo->hasBestTypeOverrideMatch() ?
                    $expressionTypeInfo->getBestTypeOverrideMatchLine() :
                    $line;

                $type = $this->fileTypeResolverFactory->create($file)->resolve($type, $typeLine);
            }

            if ($isArraySyntaxTypeHint) {
                $type .= '[]';
            }

            $resolvedTypes[] = $type;
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
