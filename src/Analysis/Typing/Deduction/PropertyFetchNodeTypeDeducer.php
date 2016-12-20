<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpParser\Node;
use PhpParser\PrettyPrinterAbstract;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\PropertyFetch} node.
 */
class PropertyFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    use LocalExpressionTypeDeductionTrait;

    // /**
    //  * @var NodeTypeDeducerFactoryInterface
    //  */
    // protected $nodeTypeDeducerFactory;
    //
    // /**
    //  * @var PrettyPrinterAbstract
    //  */
    // protected $prettyPrinter;

    /**
     * @var ClasslikeInfoBuilder
     */
    protected $classlikeInfoBuilder;

    /**
     * @param ClasslikeInfoBuilder                                            $classlikeInfoBuilder
     * @param \PhpParser\Parser                                               $parser
     * @param \PhpIntegrator\Parsing\DocblockParser                           $docblockParser
     * @param PrettyPrinterAbstract                                           $prettyPrinter
     * @param \PhpIntegrator\Analysis\Typing\FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param \PhpIntegrator\Analysis\Typing\TypeAnalyzer                     $typeAnalyzer
     * @param NodeTypeDeducerFactoryInterface                                 $nodeTypeDeducerFactory
     */
    public function __construct(
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        \PhpParser\Parser $parser,
        \PhpIntegrator\Parsing\DocblockParser $docblockParser,
        PrettyPrinterAbstract $prettyPrinter,
        \PhpIntegrator\Analysis\Typing\FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        \PhpIntegrator\Analysis\Typing\TypeAnalyzer $typeAnalyzer,
        NodeTypeDeducerFactoryInterface $nodeTypeDeducerFactory
    ) {
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->parser = $parser;
        $this->docblockParser = $docblockParser;
        $this->prettyPrinter = $prettyPrinter;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->nodeTypeDeducerFactory = $nodeTypeDeducerFactory;
    }

    /**
     * @inheritDoc
     */
    public function deduceTypesFromNode(Node $node, $file, $code, $offset)
    {
        if (!$node instanceof Node\Expr\PropertyFetch && !$node instanceof Node\Expr\StaticPropertyFetch) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromPropertyFetchNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $node
     * @param string|null                                           $file
     * @param string                                                $code
     * @param int                                                   $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromPropertyFetchNode(Node\Expr $node, $file, $code, $offset)
    {
        if ($node->name instanceof Node\Expr) {
            return []; // Can't currently deduce type of an expression such as "$this->{$foo}";
        }

        $objectNode = ($node instanceof Node\Expr\PropertyFetch) ? $node->var : $node->class;

        if ($node instanceof Node\Expr\PropertyFetch) {
            $objectNode = $node->var;
        } else {
            $objectNode = $node->class;
        }

        $typesOfVar = [];

        try {
            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($objectNode);

            $typesOfVar = $nodeTypeDeducer->deduceTypesFromNode($objectNode, $file, $code, $offset);
        } catch (UnexpectedValueException $e) {
            return [];
        }

        $typeMap = [];

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
                    $typeMap += array_combine($fetchedTypes, array_fill(0, count($fetchedTypes), true));
                }
            }
        }

        // We use an associative array so we automatically avoid duplicate types.
        $types = array_keys($typeMap);

        $expressionString = $this->prettyPrinter->prettyPrintExpr($node);

        $localTypes = $this->getLocalExpressionTypes($file, $code, $expressionString, $offset, $types);

        if (!empty($localTypes)) {
            return $localTypes;
        }

        return $types;
    }
}
