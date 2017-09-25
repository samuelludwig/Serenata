<?php

namespace PhpIntegrator\GotoDefinition;

use UnexpectedValueException;

use PhpIntegrator\Analysis\Node\MethodCallMethodInfoRetriever;

use PhpIntegrator\Indexing\Structures;

use PhpParser\Node;

/**
 * Locates the definition of the function called in {@see Node\Expr\StaticCall} nodes.
 */
class StaticMethodCallNodeDefinitionLocator
{
    /**
     * @var MethodCallMethodInfoRetriever
     */
    private $methodCallMethodInfoRetriever;

    /**
     * @param MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever
     */
    public function __construct(MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever)
    {
        $this->methodCallMethodInfoRetriever = $methodCallMethodInfoRetriever;
    }

    /**
     * @param Node\Expr\StaticCall $node
     * @param Structures\File      $file
     * @param string               $code
     * @param int                  $offset
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    public function locate(
        Node\Expr\StaticCall $node,
        Structures\File $file,
        string $code,
        int $offset
    ): GotoDefinitionResult {
        $infoElements = $this->methodCallMethodInfoRetriever->retrieve($node, $file, $code, $offset);

        if (empty($infoElements)) {
            throw new UnexpectedValueException('No method call information was found for node');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        $info = array_shift($infoElements);

        return new GotoDefinitionResult($info['filename'], $info['startLine']);
    }
}
