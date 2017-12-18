<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use UnexpectedValueException;

use PhpIntegrator\Analysis\Visiting\NodeFetchingVisitor;

use PhpIntegrator\Indexing\Structures\File;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

/**
 * Autocompletion provider that first checks if autocompletion suggestions apply at the requested offset and, if so,
 * delegates to another provider.
 */
final class ApplicabilityCheckingAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var AutocompletionProviderInterface
     */
    private $delegate;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var AutocompletionApplicabilityCheckerInterface
     */
    private $autocompletionApplicabilityChecker;

    /**
     * @param AutocompletionProviderInterface             $delegate
     * @param Parser                                      $parser
     * @param AutocompletionApplicabilityCheckerInterface $autocompletionApplicabilityChecker
     */
    public function __construct(
        AutocompletionProviderInterface $delegate,
        Parser $parser,
        AutocompletionApplicabilityCheckerInterface $autocompletionApplicabilityChecker
    ) {
        $this->delegate = $delegate;
        $this->parser = $parser;
        $this->autocompletionApplicabilityChecker = $autocompletionApplicabilityChecker;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $nodes = [];

        try {
            $nodes = $this->getNodesFromCode($code);
        } catch (UnexpectedValueException $e) {
            return [];
        }

        $node = $this->findNodeAt($nodes, $offset);

        if ($node !== null && $this->autocompletionApplicabilityChecker->doesApplyTo($node)) {
            return $this->delegate->provide($file, $code, $offset);
        } elseif ($node === null && $this->autocompletionApplicabilityChecker->doesApplyOutsideNodes()) {
            return $this->delegate->provide($file, $code, $offset);
        }

        return [];
    }

    /**
     * @param array $nodes
     * @param int   $position
     *
     * @return Node|null
     */
    private function findNodeAt(array $nodes, int $position): ?Node
    {
        $visitor = new NodeFetchingVisitor($position);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        $node = $visitor->getNode();
        $nearestInterestingNode = $visitor->getNearestInterestingNode();

        if (!$node) {
            return null;
        }

        if ($nearestInterestingNode instanceof Node\Expr\FuncCall ||
            $nearestInterestingNode instanceof Node\Expr\ConstFetch ||
            $nearestInterestingNode instanceof Node\Stmt\UseUse
        ) {
            return $nearestInterestingNode;
        }

        return ($node instanceof Node\Name || $node instanceof Node\Identifier) ? $node : $nearestInterestingNode;
    }

    /**
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return Node[]
     */
    private function getNodesFromCode(string $code): array
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
    private function getErrorHandler(): ErrorHandler\Collecting
    {
        return new ErrorHandler\Collecting();
    }
}
