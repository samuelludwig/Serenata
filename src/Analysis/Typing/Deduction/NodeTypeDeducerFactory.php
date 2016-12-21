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

use PhpParser\Node;
use PhpParser\PrettyPrinterAbstract;

/**
 * Factory that creates objects implementing {@see NodeTypeDeducerInterface}.
 */
class NodeTypeDeducerFactory implements NodeTypeDeducerFactoryInterface
{
    /**
     * @var FileClassListProviderInterface
     */
    protected $fileClassListProvider;

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
     * @var LocalTypeScanner
     */
    protected $localTypeScanner;

    /**
     * @param FileClassListProviderInterface   $fileClassListProvider
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param IndexDatabase                    $indexDatabase
     * @param ClasslikeInfoBuilder             $classlikeInfoBuilder
     * @param FunctionConverter                $functionConverter
     * @param ConstantConverter                $constantConverter
     * @param PrettyPrinterAbstract            $prettyPrinter
     * @param LocalTypeScanner                 $localTypeScanner
     */
    public function __construct(
        FileClassListProviderInterface $fileClassListProvider,
        TypeAnalyzer $typeAnalyzer,
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        IndexDatabase $indexDatabase,
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        FunctionConverter $functionConverter,
        ConstantConverter $constantConverter,
        PrettyPrinterAbstract $prettyPrinter,
        LocalTypeScanner $localTypeScanner
    ) {
        $this->fileClassListProvider = $fileClassListProvider;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->indexDatabase = $indexDatabase;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->functionConverter = $functionConverter;
        $this->constantConverter = $constantConverter;
        $this->prettyPrinter = $prettyPrinter;
        $this->localTypeScanner = $localTypeScanner;
    }

    /**
     * @inheritDoc
     */
    public function create(Node $node)
    {
        if ($node instanceof Node\Expr\Variable) {
            return new VariableNodeTypeDeducer($this->localTypeScanner);
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
            return new ArrayDimFetchNodeTypeDeducer($this->typeAnalyzer, $this->getGenericNodeTypeDeducer());
        } elseif ($node instanceof Node\Expr\Closure) {
            return new ClosureNodeTypeDeducer();
        } elseif ($node instanceof Node\Expr\New_) {
            return new NewNodeTypeDeducer($this->getGenericNodeTypeDeducer());
        } elseif ($node instanceof Node\Expr\Clone_) {
            return new CloneNodeTypeDeducer($this);
        } elseif ($node instanceof Node\Expr\Array_) {
            return new ArrayNodeTypeDeducer();
        } elseif ($node instanceof Parsing\Node\Keyword\Self_) {
            return new SelfNodeTypeDeducer($this->getGenericNodeTypeDeducer());
        } elseif ($node instanceof Parsing\Node\Keyword\Static_) {
            return new StaticNodeTypeDeducer($this->getGenericNodeTypeDeducer());
        } elseif ($node instanceof Parsing\Node\Keyword\Parent_) {
            return new ParentNodeTypeDeducer($this->getGenericNodeTypeDeducer());
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
            return new MethodCallNodeTypeDeducer($this->getGenericNodeTypeDeducer(), $this->classlikeInfoBuilder);
        } elseif ($node instanceof Node\Expr\PropertyFetch || $node instanceof Node\Expr\StaticPropertyFetch) {
            return new PropertyFetchNodeTypeDeducer(
                $this->localTypeScanner,
                $this->getGenericNodeTypeDeducer(),
                $this->prettyPrinter,
                $this->classlikeInfoBuilder
            );
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            return new ClassConstFetchNodeTypeDeducer($this->getGenericNodeTypeDeducer(), $this->classlikeInfoBuilder);
        } elseif ($node instanceof Node\Expr\Assign) {
            return new AssignNodeTypeDeducer($this->getGenericNodeTypeDeducer());
        } elseif ($node instanceof Node\Stmt\ClassLike) {
            return new ClassLikeNodeTypeDeducer();
        } elseif ($node instanceof Node\Expr\Ternary) {
            return new TernaryNodeTypeDeducer($this->getGenericNodeTypeDeducer());
        }

        throw new NoTypeDeducerFoundException("No type deducer known for node of type " . get_class($node));
    }

    /**
     * @return NodeTypeDeducer
     */
    protected function getGenericNodeTypeDeducer()
    {
        return new NodeTypeDeducer($this);
    }
}
