<?php

namespace Serenata\SignatureHelp;

use LogicException;
use UnexpectedValueException;

use PhpParser\Node;

use Serenata\Analysis\Node\FunctionFunctionInfoRetriever;
use Serenata\Analysis\Node\MethodCallMethodInfoRetriever;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;

use Serenata\Common\Position;

use Serenata\PrettyPrinting\FunctionParameterPrettyPrinter;

use Serenata\Utility\NodeHelpers;
use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Retrieves invocation information for function and method calls.
 */
final class SignatureHelpRetriever
{
    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $nodeAtOffsetLocator;

    /**
     * @var FunctionFunctionInfoRetriever
     */
    private $functionFunctionInfoRetriever;

    /**
     * @var MethodCallMethodInfoRetriever
     */
    private $methodCallMethodInfoRetriever;

    /**
     * @var FunctionParameterPrettyPrinter
     */
    private $functionParameterPrettyPrinter;

    /**
     * @param NodeAtOffsetLocatorInterface   $nodeAtOffsetLocator
     * @param FunctionFunctionInfoRetriever  $functionFunctionInfoRetriever
     * @param MethodCallMethodInfoRetriever  $methodCallMethodInfoRetriever
     * @param FunctionParameterPrettyPrinter $functionParameterPrettyPrinter
     */
    public function __construct(
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        FunctionFunctionInfoRetriever $functionFunctionInfoRetriever,
        MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever,
        FunctionParameterPrettyPrinter $functionParameterPrettyPrinter
    ) {
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->functionFunctionInfoRetriever = $functionFunctionInfoRetriever;
        $this->methodCallMethodInfoRetriever = $methodCallMethodInfoRetriever;
        $this->functionParameterPrettyPrinter = $functionParameterPrettyPrinter;
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return SignatureHelp|null
     */
    public function get(TextDocumentItem $textDocumentItem, Position $position): ?SignatureHelp
    {
        $nodes = [];

        try {
            $node = $this->getNodeAt($textDocumentItem, $position);

            return $this->getSignatureHelpForNode($node, $textDocumentItem, $position);
        } catch (UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return Node
     */
    private function getNodeAt(TextDocumentItem $textDocumentItem, Position $position): Node
    {
        $result = $this->nodeAtOffsetLocator->locate($textDocumentItem, $position);

        $node = $result->getNode();

        if ($node === null) {
            throw new UnexpectedValueException(
                'No node found at location ' . $position->getLine() . ':' . $position->getCharacter()
            );
        }

        return $node;
    }

    /**
     * @param Node             $node
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return SignatureHelp
     */
    private function getSignatureHelpForNode(
        Node $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): SignatureHelp {
        $invocationNode = NodeHelpers::findNodeOfAnyTypeInNodePath(
            $node,
            Node\Expr\FuncCall::class,
            Node\Expr\StaticCall::class,
            Node\Expr\MethodCall::class,
            Node\Expr\New_::class
        );

        $offset = $position->getAsByteOffsetInString($textDocumentItem->getText(), PositionEncoding::VALUE);

        if ($invocationNode === null) {
            throw new UnexpectedValueException('No node supporting signature help found at location');
        }

        $argumentNode = NodeHelpers::findNodeOfAnyTypeInNodePath($node, Node\Arg::class);

        $closureNode = NodeHelpers::findNodeOfAnyTypeInNodePath(
            $node,
            Node\Expr\Closure::class,
            Node\Expr\ArrowFunction::class
        );

        if ($argumentNode !== null) {
            if ($argumentNode->getAttribute('parent') !== $invocationNode) {
                // Usually the invocationNode will be the parent, but in case we're on the name of a nested function
                // call, we may have received the wrong node instead. We also can't fetch the argument beforehand, as
                // we may be at a location in between arguments where $argumentNode will be null (as its range only
                // spans the actual argument, without whitespace and optional comma).
                //
                // For example: foo(ba|r('blah')) where "|" is where the requested position is at.
                $nodeNameEndFilePosition = null;

                if ($invocationNode instanceof Node\Expr\FuncCall) {
                    $nodeNameEndFilePosition = $invocationNode->name->getAttribute('endFilePos') + 1;
                } elseif ($invocationNode instanceof Node\Expr\New_) {
                    $nodeNameEndFilePosition = $invocationNode->class->getAttribute('endFilePos') + 1;
                } elseif ($invocationNode instanceof Node\Expr\MethodCall ||
                    $invocationNode instanceof Node\Expr\StaticCall
                ) {
                    $nodeNameEndFilePosition = $invocationNode->name->getAttribute('endFilePos') + 1;
                } else {
                    throw new LogicException(
                        'Unexpected invocation node type "' . get_class($invocationNode) . '" encountered'
                    );
                }

                if ($offset <= $nodeNameEndFilePosition) {
                    $invocationNode = $argumentNode->getAttribute('parent');
                }
            } elseif ($closureNode !== null) {
                // When a closure is used as an argument to a call, we may still show signature help for the call, but
                // not inside the closure's body, as a new scope begins there.
                if ($offset > $closureNode->getAttribute('bodyStartFilePos') &&
                    $offset <= $closureNode->getAttribute('bodyEndFilePos')
                ) {
                    throw new UnexpectedValueException(
                        'No node supporting signature help found inside closure at location'
                    );
                }
            }
        }

        $argumentIndex = $this->getArgumentIndex($invocationNode, $textDocumentItem, $position);

        return $this->generateResponseFor($invocationNode, $argumentIndex, $textDocumentItem, $position);
    }

    /**
     * @param Node\Expr\FuncCall|Node\Expr\StaticCall|Node\Expr\MethodCall|Node\Expr\New_ $invocationNode
     * @param TextDocumentItem                                                            $textDocumentItem
     * @param Position                                                                    $position
     *
     * @return int
     */
    private function getArgumentIndex(
        Node $invocationNode,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): int {
        $arguments = $invocationNode->args;

        $code = $textDocumentItem->getText();
        $offset = $position->getAsByteOffsetInString($textDocumentItem->getText(), PositionEncoding::VALUE);

        if (count($arguments) === 0) {
            for ($i = $offset; $i < $invocationNode->getAttribute('endFilePos'); ++$i) {
                if ($code[$i] === '(') {
                    throw new UnexpectedValueException(
                        'Found node supporting signature help at location ' . $offset . ', but it\'s outside the ' .
                        'range of the argument list'
                    );
                }
            }

            return 0;
        }

        $startOfArgumentList = null;

        for ($i = $arguments[0]->getAttribute('startFilePos') - 1; $i >= 0; --$i) {
            if ($code[$i] === '(') {
                $startOfArgumentList = $i;

                break;
            }
        }

        $endOfArgumentList = null;

        for ($i = $arguments[count($arguments) - 1]->getAttribute('endFilePos') + 1; $i < mb_strlen($code); ++$i) {
            if ($code[$i] === ')') {
                $endOfArgumentList = $i+1;

                break;
            }
        }

        if ($offset <= $startOfArgumentList || $offset > $endOfArgumentList) {
            throw new UnexpectedValueException(
                'Found node supporting signature help at location ' . $offset . ', but it\'s outside the ' .
                'range of the argument list'
            );
        }

        $argumentNodeAfter = null;
        $argumentNodeBefore = null;

        foreach ($arguments as $argument) {
            // NOTE: Node end positions are inclusive rather than exclusive.
            if ($offset >= ($argument->getAttribute('endFilePos')+1)) {
                $argumentNodeBefore = $argument;
            }

            if ($argumentNodeAfter === null && $offset <= $argument->getAttribute('startFilePos')) {
                $argumentNodeAfter = $argument;
            }
        }

        if ($argumentNodeBefore === null) {
            return 0;
        }

        $isBeforeComma = true;

        for ($i = $argumentNodeBefore->getAttribute('endFilePos') + 1; $i < $offset; ++$i) {
            if ($code[$i] === ',') {
                $isBeforeComma = false;

                break;
            }
        }

        $argumentIndex = array_search($argumentNodeBefore, $arguments, true);

        assert(!is_string($argumentIndex), 'Got unexpected string as index for an array that should be sequential');

        if ($argumentIndex === false) {
            return 0;
        }

        // By offsetting from the argument before, we catch the case where there is a syntax error, which causes no
        // last node to exist.
        return $argumentIndex + ($isBeforeComma ? 0 : 1);
    }

    /**
     * @param Node             $node
     * @param int              $argumentIndex
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UnexpectedValueException
     *
     * @return SignatureHelp
     */
    private function generateResponseFor(
        Node $node,
        int $argumentIndex,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): SignatureHelp {
        $name = null;
        $parameters = [];
        $documentation = null;

        if ($node instanceof Node\Expr\MethodCall ||
            $node instanceof Node\Expr\StaticCall ||
            $node instanceof Node\Expr\New_
        ) {
            $methodInfoElements = $this->methodCallMethodInfoRetriever->retrieve($node, $textDocumentItem, $position);

            if (count($methodInfoElements) === 0) {
                throw new UnexpectedValueException('Method to fetch signature help for was not found');
            }

            // FIXME: There could be multiple matches, return multiple signatures in that case.
            return $this->generateResponseFromFunctionInfo(array_shift($methodInfoElements), $argumentIndex);
        } elseif ($node instanceof Node\Expr\FuncCall) {
            $functionInfo = $this->functionFunctionInfoRetriever->retrieve($node, $textDocumentItem, $position);

            return $this->generateResponseFromFunctionInfo($functionInfo, $argumentIndex);
        }

        throw new UnexpectedValueException(
            'Could not determine signature help for node of type ' . get_class($node)
        );
    }

    /**
     * @param array<string,mixed> $functionInfo
     * @param int                 $argumentIndex
     *
     * @return SignatureHelp
     */
    private function generateResponseFromFunctionInfo(array $functionInfo, int $argumentIndex): SignatureHelp
    {
        $name = $functionInfo['name'];
        $documentation = $functionInfo['shortDescription'];
        $parameters = $this->getResponseParametersForFunctionParameters($functionInfo['parameters']);

        $argumentIndex = $this->getNormalizedFunctionArgumentIndex($functionInfo, $argumentIndex);

        $signature = new SignatureInformation(
            $this->formatSignatureLabel($name, $parameters),
            $documentation,
            $parameters
        );

        return new SignatureHelp([$signature], 0, $argumentIndex);
    }

    /**
     * @param string                 $name
     * @param ParameterInformation[] $parameters
     *
     * @return string
     */
    private function formatSignatureLabel(string $name, array $parameters): string
    {
        return $name . '(' . implode(', ', array_map(function (ParameterInformation $parameterInformation): string {
            return $parameterInformation->getLabel();
        }, $parameters)) . ')';
    }

    /**
     * @param array<string,mixed> $functionInfo
     * @param int                 $argumentIndex
     *
     * @return int|null
     */
    private function getNormalizedFunctionArgumentIndex(array $functionInfo, int $argumentIndex): ?int
    {
        $parameterCount = count($functionInfo['parameters']);

        if ($argumentIndex >= $parameterCount) {
            if ($argumentIndex === 0 && $parameterCount === 0) {
                // First "argument" here isn't actually an argument, so we return null as index so we can still show
                // signature help for functions without arguments.
                return null;
            } elseif ($parameterCount > 0 && $functionInfo['parameters'][$parameterCount - 1]['isVariadic']) {
                return $parameterCount - 1;
            } else {
                throw new UnexpectedValueException(
                    "Argument index {$argumentIndex} is out of bounds, only {$parameterCount} parameters supported"
                );
            }
        }

        return $argumentIndex;
    }

    /**
     * @param array<array<string,mixed>> $parameters
     *
     * @return ParameterInformation[]
     */
    private function getResponseParametersForFunctionParameters(array $parameters): array
    {
        $responseParameters = [];

        foreach ($parameters as $parameter) {
            $responseParameters[] = $this->getResponseParametersForFunctionParameter($parameter);
        }

        return $responseParameters;
    }

    /**
     * @param array<string,mixed> $parameter
     *
     * @return ParameterInformation
     */
    private function getResponseParametersForFunctionParameter(array $parameter): ParameterInformation
    {
        $label = $this->functionParameterPrettyPrinter->print($parameter);

        return new ParameterInformation($label, $parameter['description']);
    }
}
