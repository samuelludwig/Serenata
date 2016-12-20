<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use PhpIntegrator\Parsing;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Conversion\FunctionConverter;
use PhpIntegrator\Analysis\Conversion\ConstantConverter;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\FileClassListProviderInterface;
use PhpIntegrator\Analysis\Typing\FileTypeResolverFactoryInterface;

use PhpIntegrator\Indexing\IndexDatabase;

use PhpIntegrator\Parsing\PartialParser;
use PhpIntegrator\Parsing\DocblockParser;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

/**
 * Factory that creates objects implementing {@see NodeTypeDeducerInterface}.
 */
class NodeTypeDeducerFactory implements NodeTypeDeducerFactoryInterface
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
     * @inheritDoc
     */
    public function create(Node $node)
    {
        if ($node instanceof Node\Expr\Variable) {
            return new VariableNodeTypeDeducer(
                $this->parser,
                $this->docblockParser,
                $this->prettyPrinter,
                $this->fileTypeResolverFactory,
                $this->typeAnalyzer,
                $this
            );
        } elseif ($node instanceof Node\Scalar\LNumber) {
            return new LNumberNodeTypeDeducer();
        } elseif ($node instanceof Node\Scalar\DNumber) {
            return new DNumberNodeTypeDeducer();
        } elseif ($node instanceof Node\Scalar\String_) {
            return new StringNodeTypeDeducer();
        } elseif ($node instanceof Node\Expr\ConstFetch) {
            return new ConstFetchNodeTypeDeducer(
                $this->fileTypeResolverFactory,
                $this->indexDatabase,
                $this->constantConverter
            );
        } elseif ($node instanceof Node\Expr\ArrayDimFetch) {
            return new ArrayDimFetchNodeTypeDeducer($this->typeAnalyzer, $this);
        } elseif ($node instanceof Node\Expr\Closure) {
            return new ClosureNodeTypeDeducer();
        } elseif ($node instanceof Node\Expr\New_) {
            return new NewNodeTypeDeducer($this);
        } elseif ($node instanceof Node\Expr\Clone_) {
            return new CloneNodeTypeDeducer($this);
        } elseif ($node instanceof Node\Expr\Array_) {
            return new ArrayNodeTypeDeducer();
        } elseif ($node instanceof Parsing\Node\Keyword\Self_) {
            return new SelfNodeTypeDeducer($this);
        } elseif ($node instanceof Parsing\Node\Keyword\Static_) {
            return new StaticNodeTypeDeducer($this);
        } elseif ($node instanceof Parsing\Node\Keyword\Parent_) {
            return new ParentNodeTypeDeducer($this);
        } elseif ($node instanceof Node\Name) {
            return new NameNodeTypeDeducer(
                $this->typeAnalyzer,
                $this->classlikeInfoBuilder,
                $this->fileClassListProvider,
                $this->fileTypeResolverFactory
            );
        } elseif ($node instanceof Node\Expr\FuncCall) {
            return new FuncCallNodeTypeDeducer($this->indexDatabase, $this->functionConverter);
        } elseif ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall) {
            return new MethodCallNodeTypeDeducer($this, $this->classlikeInfoBuilder);
        } elseif ($node instanceof Node\Expr\PropertyFetch || $node instanceof Node\Expr\StaticPropertyFetch) {
            return new PropertyFetchNodeTypeDeducer(
                $this->classlikeInfoBuilder,
                $this->parser,
                $this->docblockParser,
                $this->prettyPrinter,
                $this->fileTypeResolverFactory,
                $this->typeAnalyzer,
                $this
            );
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            return new ClassConstFetchNodeTypeDeducer($this, $this->classlikeInfoBuilder);
        } elseif ($node instanceof Node\Expr\Assign) {
            return new AssignNodeTypeDeducer($this);
        } elseif ($node instanceof Node\Stmt\ClassLike) {
            return new ClassLikeNodeTypeDeducer();
        } elseif ($node instanceof Node\Expr\Ternary) {
            return new TernaryNodeTypeDeducer($this);
        }

        throw new NoTypeDeducerFoundException("No type deducer known for node of type " . get_class($node));
    }
}
