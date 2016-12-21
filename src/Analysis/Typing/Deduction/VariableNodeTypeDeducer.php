<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\Variable} node.
 */
class VariableNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    use LocalExpressionTypeDeductionTrait;

    /**
     * @param \PhpParser\Parser                                               $parser
     * @param \PhpIntegrator\Parsing\DocblockParser                           $docblockParser
     * @param \PhpParser\PrettyPrinterAbstract                                $prettyPrinter
     * @param \PhpIntegrator\Analysis\Typing\FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param \PhpIntegrator\Analysis\Typing\TypeAnalyzer                     $typeAnalyzer
     * @param NodeTypeDeducerInterface                                        $nodeTypeDeducer
     */
    public function __construct(
        \PhpParser\Parser $parser,
        \PhpIntegrator\Parsing\DocblockParser $docblockParser,
        \PhpParser\PrettyPrinterAbstract $prettyPrinter,
        \PhpIntegrator\Analysis\Typing\FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        \PhpIntegrator\Analysis\Typing\TypeAnalyzer $typeAnalyzer,
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
     * @inheritDoc
     */
    public function deduce(Node $node, $file, $code, $offset)
    {
        if (!$node instanceof Node\Expr\Variable) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromVariableNode($node, $file, $code, $offset);
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
}
