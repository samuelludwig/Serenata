<?php

namespace PhpIntegrator\Refactoring;

use AssertionError;
use UnexpectedValueException;

use PhpIntegrator\Analysis\NodeAtOffsetLocatorInterface;

use PhpIntegrator\Analysis\Typing\TypeNormalizerInterface;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

use PhpIntegrator\Common\Range;
use PhpIntegrator\Common\Position;

use PhpIntegrator\Utility\TextEdit;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\ErrorHandler;

/**
 * Creates {@see TextEdit}s that insert use statements (imports).
 */
class UseStatementInsertionCreator
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var NodeAtOffsetLocatorInterface
     */
    private $nodeAtOffsetLocator;

    /**
     * @var TypeNormalizerInterface
     */
    private $typeNormalizer;

    /**
     * @param Parser                       $parser
     * @param NodeAtOffsetLocatorInterface $nodeAtOffsetLocator
     * @param TypeNormalizerInterface      $typeNormalizer
     */
    public function __construct(
        Parser $parser,
        NodeAtOffsetLocatorInterface $nodeAtOffsetLocator,
        TypeNormalizerInterface $typeNormalizer
    ) {
        $this->parser = $parser;
        $this->nodeAtOffsetLocator = $nodeAtOffsetLocator;
        $this->typeNormalizer = $typeNormalizer;
    }

    /**
     * @param string $name
     * @param string $kind                    {@see UseStatementKind}
     * @param string $code
     * @param bool   $allowAdditionalNewlines
     * @param int    $position
     *
     * @throws UseStatementAlreadyExistsException
     * @throws UseStatementInsertionCreationException
     *
     * @return TextEdit
     */
    public function create(
        string $name,
        string $kind,
        string $code,
        int $position,
        bool $allowAdditionalNewlines
    ): TextEdit {
        $this->enforceThatCreationIsPossible($name, $kind, $code, $position);

        $textToInsert = "use {$name};\n";

        $normalizedName = $this->typeNormalizer->getNormalizedFqcn($name);

        $line = $this->locateBestZeroIndexedLineForInsertion($normalizedName, $kind, $code, $position);
        $shouldInsertBelowBestLine = $this->shouldInsertBelowLineOfBestMatch($normalizedName, $kind, $code, $position);

        if ($shouldInsertBelowBestLine) {
            ++$line;
        }

        if ($allowAdditionalNewlines && $this->shouldAddAdditionalNewline($normalizedName, $kind, $code, $position)) {
            if ($shouldInsertBelowBestLine) {
                $textToInsert = "\n" . $textToInsert;
            } else {
                $textToInsert .= "\n";
            }
        }


        return new TextEdit(
            new Range(new Position($line, 0), new Position($line, 0)),
            $textToInsert
        );
    }

    /**
     * @param string $name
     * @param string $kind
     * @param string $code
     * @param int    $position
     */
    private function enforceThatCreationIsPossible(
        string $name,
        string $kind,
        string $code,
        int $position
    ): void {
        if ($this->isUseStatementAlreadyPresent($name, $kind, $code, $position)) {
            throw new UseStatementAlreadyExistsException(
                'Use statement for ' . $name . ' with kind ' . $kind . 'already exists'
            );
        }

        $namespaceNode = $this->locateActiveNamespaceAt($code, $position);

        if (mb_strpos($name, '\\') === false && ($namespaceNode === null || $namespaceNode->name === null)) {
            throw new NonCompoundNameInAnonymousNamespaceException(
                'Adding use statements for non-compound name in anonymous namespaces is prohibited as it generates ' .
                'a warning in PHP'
            );
        } elseif ($namespaceNode !== null && $namespaceNode->name !== null && $name === $namespaceNode->name->toString()) {
            throw new UseStatementEqualsNamespaceException(
                'Can not add use statement with same name as containing namespace'
            );
        }
    }

    /**
     * @param string $name
     * @param string $kind
     * @param string $code
     * @param int    $position
     *
     * @return int
     */
    private function locateBestZeroIndexedLineForInsertion(string $name, string $kind, string $code, int $position): int
    {
        $bestLine = null;

        foreach ($this->retrieveRelevantUseStatements($code, $position) as $useStatement) {
            $bestLine = $useStatement->getEndLine();

            foreach ($useStatement->uses as $useUseNode) {
                $useUseNodeName = $this->getFullNameFromUseUse($useStatement, $useUseNode);

                if ($this->shouldNameBeSortedHigherThan($name, $useUseNodeName)) {
                    break 2;
                }
            }
        }

        if ($bestLine !== null) {
            return --$bestLine; // Make line zero-indexed.
        }

        return $this->determineZeroIndexedFallbackLine($code, $position);
    }

    /**
     * @param string $name
     * @param string $kind
     * @param string $code
     * @param int    $position
     *
     * @return bool
     */
    private function shouldAddAdditionalNewline(string $name, string $kind, string $code, int $position): bool
    {
        $addAdditionalNewline = false;

        foreach ($this->retrieveRelevantUseStatements($code, $position) as $useStatement) {
            $addAdditionalNewline = true;

            foreach ($useStatement->uses as $useUseNode) {
                $useUseNodeName = $this->getFullNameFromUseUse($useStatement, $useUseNode);

                $addAdditionalNewline = !$this->doShareCommonNamespacePrefix($name, $useUseNodeName);

                if ($this->shouldNameBeSortedHigherThan($name, $useUseNodeName)) {
                    break 2;
                }
            }
        }

        return $addAdditionalNewline;
    }

    /**
     * @param string $name
     * @param string $kind
     * @param string $code
     * @param int    $position
     *
     * @return bool
     */
    private function shouldInsertBelowLineOfBestMatch(string $name, string $kind, string $code, int $position): bool
    {
        $useStatements = $this->retrieveRelevantUseStatements($code, $position);

        foreach ($useStatements as $useStatement) {
            foreach ($useStatement->uses as $useUseNode) {
                $useUseNodeName = $this->getFullNameFromUseUse($useStatement, $useUseNode);

                if ($this->shouldNameBeSortedHigherThan($name, $useUseNodeName)) {
                    return false;
                }
            }
        }

        return !empty($useStatements);
    }

    /**
     * @param string $code
     * @param int    $position
     *
     * @return (Node\Stmt\Use_|Node\Stmt\GroupUse)[]
     */
    private function retrieveRelevantUseStatements(string $code, int $position): array
    {
        $namespaceNode = $this->locateActiveNamespaceAt($code, $position);

        if ($namespaceNode !== null) {
            return $this->collectUseStatementsFromNamespaceNode($namespaceNode);
        }

        return $this->collectUseStatementsFromCode($code);
    }

    /**
     * @param string $code
     * @param int    $position
     *
     * @return int
     */
    private function determineZeroIndexedFallbackLine(string $code, int $position): int
    {
        $namespaceNode = $this->locateActiveNamespaceAt($code, $position);

        if ($namespaceNode !== null) {
            if ($namespaceNode->name !== null) {
                return $namespaceNode->name->getEndLine() + 2 - 1;
            } else {
                return $namespaceNode->getStartLine() + 1 - 1;
            }
        }

        $nodes = $this->getNodesFromCode($code);

        if (!empty($nodes)) {
            return max($nodes[0]->getStartLine() - 1 - 1, 0);
        }

        return 2;
    }

    /**
     * @param string $name
     * @param string $kind
     * @param string $code
     * @param int    $position
     *
     * @return bool
     */
    private function isUseStatementAlreadyPresent(string $name, string $kind, string $code, int $position): bool
    {
        $useStatements = $this->retrieveRelevantUseStatements($code, $position);

        $normalizedName = $this->typeNormalizer->getNormalizedFqcn($name);

        return !empty(array_filter($useStatements, function (Node\Stmt $useStatement) use ($normalizedName): bool {
            /** @var Node\Stmt\Use_|Node\Stmt\GroupUse $useStatement */
            foreach ($useStatement->uses as $useUseNode) {
                if ($this->getFullNameFromUseUse($useStatement, $useUseNode) === $normalizedName) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @param Node\Stmt\Use_|Node\Stmt\GroupUse $useStatement
     * @param Node\Stmt\UseUse                  $useUseNode
     *
     * @return string
     */
    private function getFullNameFromUseUse(Node\Stmt $useStatement, Node\Stmt\UseUse $useUseNode): string
    {
        $prefix = '';

        if ($useStatement instanceof Node\Stmt\GroupUse) {
            $prefix = ((string) $useStatement->prefix) . '\\';
        };

        return $this->typeNormalizer->getNormalizedFqcn($prefix . $useUseNode->name);
    }

    /**
     * Returns a boolean indicating if the specified class names share a common namespace prefix.
     *
     * @param string $firstClassName
     * @param string $secondClassName
     *
     * @return bool
     */
    private function doShareCommonNamespacePrefix(string $firstClassName, string $secondClassName): bool
    {
        $firstClassNameParts = explode('\\', $firstClassName);
        $secondClassNameParts = explode('\\', $secondClassName);

        array_pop($firstClassNameParts);
        array_pop($secondClassNameParts);

        return implode('\\', $firstClassNameParts) === implode('\\', $secondClassNameParts);
    }

    /**
     * @param string $name
     * @param string $referenceName
     *
     * @return bool
     */
    private function shouldNameBeSortedHigherThan(string $name, string $referenceName): bool
    {
        return $this->scoreClassName($name, $referenceName) <= 0;
    }

    /**
     * Scores the first class name against the second, indicating how much they 'match' each other.
     *
     * This can be used to e.g. find an appropriate location to place a class in an existing list of classes.
     *
     * @param string $firstClassName
     * @param string $secondClassName
     *
     * @return float A floating point number that represents the score.
     */
    private function scoreClassName(string $firstClassName, string $secondClassName): float
    {
        $maxLength = 0;
        $totalScore = 0;

        $firstClassNameParts = explode('\\', $firstClassName);
        $secondClassNameParts = explode('\\', $secondClassName);

        $maxLength = min(count($firstClassNameParts), count($secondClassNameParts));

        // At this point, both FQSEN's share a common namespace, e.g. A\B and A\B\C\D, or XMLElement and XMLDocument.
        // The one with the most namespace parts ends up last.
        if (count($firstClassNameParts) < count($secondClassNameParts)) {
            return -1;
        } elseif (count($firstClassNameParts) > count($secondClassNameParts)) {
            return 1;
        }

        if ($maxLength >= 3) {
            for ($i = 0; $i <= $maxLength; ++$i) {
                if ($firstClassNameParts[$i] !== $secondClassNameParts[$i]) {
                    if (mb_strlen($firstClassNameParts[$i]) === mb_strlen($secondClassNameParts[$i])) {
                        return substr_compare($firstClassNameParts[$i], $secondClassNameParts[$i], 0);
                    }

                    return mb_strlen($firstClassNameParts[$i]) <=> mb_strlen($secondClassNameParts[$i]);
                }
            }

            throw new AssertionError('Both names are identical, which should not happen');
        }

        if (mb_strlen($firstClassName) === mb_strlen($secondClassName)) {
            return substr_compare($firstClassName, $secondClassName, 0);
        }

        // Both items have share the same namespace, sort from shortest to longest last word (class, interface, ...).
        return mb_strlen($firstClassName) <=> mb_strlen($secondClassName);
    }

    /**
     * @param string $code
     * @param int    $position
     *
     * @return Node\Stmt\Namespace_|null
     */
    private function locateActiveNamespaceAt(string $code, int $position): ?Node\Stmt\Namespace_
    {
        $result = $this->nodeAtOffsetLocator->locate($code, $position);

        $node = $result->getNode();

        while ($node !== null) {
            if ($node instanceof Node\Stmt\Namespace_) {
                return $node;
            }

            $node = $node->getAttribute('parent', false);

            if ($node === false) {
                throw new AssertionError('No required parent metadata attached to node');
            }
        }

        $nodes = [];

        try {
            $nodes = $this->getNodesFromCode($code);
        } catch (UnexpectedValueException $e) {
            throw new UseStatementInsertionCreationException(
                'Could not parse code needed for use statement insertion creation',
                0,
                $e
            );
        }

        foreach ($nodes as $node) {
            $endFilePos = $node->getAttribute('endFilePos');
            $startFilePos = $node->getAttribute('startFilePos');

            if ($startFilePos > $position) {
                break;
            } elseif ($node instanceof Node\Stmt\Namespace_) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @param Node\Stmt\Namespace_ $namespace
     *
     * @return (Node\Stmt\Use_|Node\Stmt\GroupUse)[]
     */
    private function collectUseStatementsFromNamespaceNode(Node\Stmt\Namespace_ $namespace): array
    {
        return array_filter($namespace->stmts, function (Node\Stmt $statement): bool {
            return $statement instanceof Node\Stmt\Use_ || $statement instanceof Node\Stmt\GroupUse;
        });
    }

    /**
     * @param string $code
     *
     * @throws UseStatementInsertionCreationException
     *
     * @return (Node\Stmt\Use_|Node\Stmt\GroupUse)[]
     */
    private function collectUseStatementsFromCode(string $code): array
    {
        $nodes = [];

        try {
            $nodes = $this->getNodesFromCode($code);
        } catch (UnexpectedValueException $e) {
            throw new UseStatementInsertionCreationException(
                'Could not parse code needed for use statement insertion creation',
                0,
                $e
            );
        }

        return $this->collectUseStatementsFromNodeArray($nodes);
    }

    /**
     * @param Node[] $nodes
     *
     * @return (Node\Stmt\Use_|Node\Stmt\GroupUse)[]
     */
    private function collectUseStatementsFromNodeArray(array $nodes): array
    {
        return array_filter($nodes, function (Node $node): bool {
            return $node instanceof Node\Stmt\Use_ || $node instanceof Node\Stmt\GroupUse;
        });
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
