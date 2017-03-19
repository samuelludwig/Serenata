<?php

namespace PhpIntegrator\SignatureHelp;

use UnexpectedValueException;

use PhpIntegrator\Analysis\Node\FunctionFunctionInfoRetriever;
use PhpIntegrator\Analysis\Node\MethodCallMethodInfoRetriever;

use PhpIntegrator\Analysis\Visiting\NodeFetchingVisitor;
use PhpIntegrator\Analysis\Visiting\ParentAttachingVisitor;
use PhpIntegrator\Analysis\Visiting\NamespaceAttachingVisitor;
use PhpIntegrator\Analysis\Visiting\ResolvedNameAttachingVisitor;

use PhpIntegrator\Parsing\ParserTokenHelper;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

/**
 * Retrieves invocation information for function and method calls.
 */
class SignatureHelpRetriever
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var ParserTokenHelper
     */
    protected $parserTokenHelper;

    /**
     * @var FunctionFunctionInfoRetriever
     */
    protected $functionFunctionInfoRetriever;

    /**
     * @var MethodCallMethodInfoRetriever
     */
    protected $methodCallMethodInfoRetriever;

    /**
     * @param Parser                        $parser
     * @param ParserTokenHelper             $parserTokenHelper
     * @param FunctionFunctionInfoRetriever $functionFunctionInfoRetriever
     * @param MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever
     */
    public function __construct(
        Parser $parser,
        ParserTokenHelper $parserTokenHelper,
        FunctionFunctionInfoRetriever $functionFunctionInfoRetriever,
        MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever
    ) {
        $this->parser = $parser;
        $this->parserTokenHelper = $parserTokenHelper;
        $this->functionFunctionInfoRetriever = $functionFunctionInfoRetriever;
        $this->methodCallMethodInfoRetriever = $methodCallMethodInfoRetriever;
    }

    /**
     * @param string $file
     * @param string $code
     * @param int    $position
     *
     * @throws UnexpectedValueException when there is no signature help to be retrieved for the location.
     * @throws UnexpectedValueException when a node type is encountered that this method doesn't know how to handle.
     *
     * @return SignatureHelp
     */
    public function get(
        string $file,
        string $code,
        int $position
    ): SignatureHelp {
        $nodes = [];

        // try {
            $nodes = $this->getNodesFromCode($code);
            $node = $this->getNodeAt($nodes, $position);

            return $this->getSignatureHelpForNode($node, $file, $code, $position);
        // } catch (UnexpectedValueException $e) {
        //     return null;
        // }
    }

    /**
     * @param array $nodes
     * @param int   $position
     *
     * @throws UnexpectedValueException
     *
     * @return Node
     */
    protected function getNodeAt(array $nodes, int $position): Node
    {
        $visitor = new NodeFetchingVisitor($position);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ResolvedNameAttachingVisitor());
        $traverser->addVisitor(new NamespaceAttachingVisitor());
        $traverser->addVisitor(new ParentAttachingVisitor());
        $traverser->addVisitor($visitor);

        $traverser->traverse($nodes);

        $node = $visitor->getNode();

        if (!$node) {
            throw new UnexpectedValueException('No node found at location ' . $position);
        }

        return $node;
    }

    /**
     * @param Node   $node
     * @param string $file
     * @param string $code
     * @param int    $position
     *
     * @throws UnexpectedValueException
     *
     * @return SignatureHelp
     */
    protected function getSignatureHelpForNode(Node $node, string $file, string $code, int $position): SignatureHelp
    {
        $invocationNode = NodeHelpers::findNodeOfAnyTypeInNodePath(
            $node,
            Node\Expr\FuncCall::class,
            Node\Expr\StaticCall::class,
            Node\Expr\MethodCall::class,
            Node\Expr\New_::class
        );

        if (!$invocationNode) {
            throw new UnexpectedValueException('No node supporting signature help found at location ' . $position);
        }

        $argumentNode = NodeHelpers::findNodeOfAnyTypeInNodePath(
            $node,
            Node\Arg::class
        );

        /** @var Node\Expr\FuncCall|Node\Expr\StaticCall|Node\Expr\MethodCall|Node\Expr\New_ $invocationNode */
        $argumentIndex = null;

        if ($argumentNode) {
            $argumentIndex = array_search($argumentNode, $invocationNode->args, true);
        } elseif (!empty($invocationNode->args)) {
            // No argument node may be found if the user is in between argument nodes. In that case, we can see where
            // we are at by locating the last argument before the requested position.
            foreach ($invocationNode->args as $i => $arg) {
                if ($arg->getAttribute('endFilePos') < $position) {
                    $argumentIndex = $i;
                }
            }

            if ($argumentIndex === null) {
                for ($i = $position+1; $i > $invocationNode->getAttribute('startFilePos'); --$i) {
                    if ($code[$i] === '(' && $position > $i) {
                        $argumentIndex = 0;
                        break;
                    }
                }

                if ($argumentIndex === null) {
                    throw new UnexpectedValueException(
                        'Found node supporting signature help at location ' . $position . ', but it\'s outside the ' .
                        'range of the argument list'
                    );
                }
            }
        } else {
            for ($i = $position; $i < $invocationNode->getAttribute('endFilePos'); ++$i) {
                if ($code[$i] === '(') {
                    throw new UnexpectedValueException(
                        'Found node supporting signature help at location ' . $position . ', but it\'s outside the ' .
                        'range of the argument list'
                    );
                }
            }

            $argumentIndex = 0;
        }

        return $this->generateResponseFor($invocationNode, $argumentIndex, $file, $code, $position);
    }

    /**
     * @param Node   $node
     * @param int    $argumentIndex
     * @param string $file
     * @param string $code
     * @param int    $offset
     *
     * @throws UnexpectedValueException
     *
     * @return SignatureHelp
     */
    protected function generateResponseFor(
        Node $node,
        int $argumentIndex,
        string $file,
        string $code,
        int $offset
    ): SignatureHelp {
        $name = null;
        $parameters = [];
        $documentation = null;

        if (
            $node instanceof Node\Expr\MethodCall ||
            $node instanceof Node\Expr\StaticCall ||
            $node instanceof Node\Expr\New_
        ) {
            $methodInfoElements = $this->methodCallMethodInfoRetriever->retrieve($node, $file, $code, $offset);

            if (empty($methodInfoElements)) {
                throw new UnexpectedValueException('Method to fetch signature help for was not found');
            }

            // FIXME: There could be multiple matches, return multiple signatures in that case.
            $methodInfo = array_shift($methodInfoElements);

            return $this->generateResponseFromFunctionInfo($methodInfo, $argumentIndex);
        } elseif ($node instanceof Node\Expr\FuncCall) {
            $functionInfo = $this->functionFunctionInfoRetriever->retrieve($node);

            return $this->generateResponseFromFunctionInfo($functionInfo, $argumentIndex);
        }

        throw new UnexpectedValueException(
            'Could not determine signature help for node of type ' . get_class($node)
        );
    }

    /**
     * @param array $functionInfo
     * @param int   $argumentIndex
     *
     * @throws UnexpectedValueException
     *
     * @return SignatureHelp
     */
    protected function generateResponseFromFunctionInfo(array $functionInfo, int $argumentIndex): SignatureHelp
    {
        $name = $functionInfo['name'];
        $documentation = $functionInfo['shortDescription'];
        $parameters = $this->getResponseParametersForFunctionParameters($functionInfo['parameters']);

        $argumentIndex = $this->getNormalizedFunctionArgumentIndex($functionInfo, $argumentIndex);

        $signature = new SignatureInformation($name, $documentation, $parameters);

        return new SignatureHelp([$signature], 0,$argumentIndex);
    }

    /**
     * @param array $functionInfo
     * @param int   $argumentIndex
     *
     * @throws UnexpectedValueException
     *
     * @return int
     */
    protected function getNormalizedFunctionArgumentIndex(array $functionInfo, int $argumentIndex): int
    {
        $parameterCount = count($functionInfo['parameters']);

        if ($argumentIndex >= $parameterCount) {
            if ($parameterCount > 0 && $functionInfo['parameters'][$parameterCount - 1]['isVariadic']) {
                return $parameterCount - 1;
            } else {
                throw new UnexpectedValueException(
                    'Parameter index ' . $argumentIndex . ' is out of bounds for function ' .
                    $functionInfo['name']
                );
            }
        }

        return $argumentIndex;
    }

    /**
     * @param array $parameters
     *
     * @return ParameterInformation[]
     */
    protected function getResponseParametersForFunctionParameters(array $parameters): array
    {
        $responseParameters = [];

        foreach ($parameters as $parameter) {
            $responseParameters[] = $this->getResponseParametersForFunctionParameter($parameter);
        }

        return $responseParameters;
    }

    /**
     * @param array $parameter
     *
     * @return ParameterInformation
     */
    protected function getResponseParametersForFunctionParameter(array $parameter): ParameterInformation
    {
        $label = '';

        if (!empty($parameter['types'])) {
            $label .= implode('|', array_map(function (array $type) {
                return $type['type'];
            }, $parameter['types']));

            $label .= ' ';
        }

        if ($parameter['isVariadic']) {
            $label .= '...';
        }

        if ($parameter['isReference']) {
            $label .= '&';
        }

        $label .= '$' . $parameter['name'];

        if ($parameter['defaultValue']) {
            $label .= ' = ' . $parameter['defaultValue'];
        }

        return new ParameterInformation($label, $parameter['description']);
    }

    /**
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return Node[]
     */
    protected function getNodesFromCode(string $code): array
    {
        $nodes = $this->parser->parse($code, $this->getErrorHandler());

        if ($nodes === null) {
            throw new UnexpectedValueException('No nodes returned after parsing code');
        }

        return $nodes;
    }

    /**
     * @return ErrorHandler\Collecting
     */
    protected function getErrorHandler(): ErrorHandler\Collecting
    {
        return new ErrorHandler\Collecting();
    }
}
