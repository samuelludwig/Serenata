<?php

namespace Serenata\Refactoring;

use LogicException;
use UnexpectedValueException;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;

use PhpParser\Parser;
use PhpParser\ErrorHandler;

use Serenata\Analysis\NodeAtOffsetLocatorInterface;

use Serenata\Analysis\Typing\TypeNormalizerInterface;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Utility\TextEdit;
use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Creates {@see TextEdit}s that insert use statements (imports).
 */
final class UseStatementInsertionCreator
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
     * @param string           $name
     * @param string           $kind                    {@see \Serenata\Analysis\Visiting\UseStatementKind}
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     * @param bool             $allowAdditionalNewlines
     *
     * @throws UseStatementInsertionCreationException
     *
     * @return TextEdit
     */
    public function create(
        string $name,
        string $kind,
        TextDocumentItem $textDocumentItem,
        Position $position,
        bool $allowAdditionalNewlines
    ): TextEdit {
        $normalizedName = $this->typeNormalizer->getNormalizedFqcn($name);

        $this->enforceThatCreationIsPossibleAndNecessary($normalizedName, $kind, $textDocumentItem, $position);

        $prefix = '';

        if ($kind === UseStatementKind::TYPE_FUNCTION) {
            $prefix = 'function ';
        } elseif ($kind === UseStatementKind::TYPE_CONSTANT) {
            $prefix = 'const ';
        }

        $textToInsert = "use {$prefix}{$name};\n";

        $line = $this->locateBestZeroIndexedLineForInsertion($normalizedName, $kind, $textDocumentItem, $position);

        $shouldInsertBelowBestLine = $this->shouldInsertBelowLineOfBestMatch(
            $normalizedName,
            $kind,
            $textDocumentItem,
            $position
        );

        if ($shouldInsertBelowBestLine) {
            ++$line;
        }

        if ($allowAdditionalNewlines &&
            $this->shouldAddAdditionalNewline($normalizedName, $kind, $textDocumentItem, $position)
        ) {
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
     * @param string           $name
     * @param string           $kind
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UseStatementInsertionCreationException
     */
    private function enforceThatCreationIsPossibleAndNecessary(
        string $name,
        string $kind,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): void {
        if ($this->isUseStatementAlreadyPresent($name, $kind, $textDocumentItem, $position)) {
            throw new UseStatementAlreadyExistsException(
                'Use statement for ' . $name . ' with kind ' . $kind . ' already exists'
            );
        }

        $namespaceNode = $this->locateActiveNamespaceAt($textDocumentItem, $position);

        if (mb_strpos($name, '\\', 1) === false && ($namespaceNode === null || $namespaceNode->name === null)) {
            throw new NonCompoundNameInAnonymousNamespaceException(
                'Adding use statements for non-compound name in anonymous namespaces is prohibited as it generates ' .
                'a warning in PHP'
            );
        } elseif ($namespaceNode === null || $namespaceNode->name === null) {
            return;
        }

        $namespaceName = $this->typeNormalizer->getNormalizedFqcn($namespaceNode->name->toString());

        if ($name === $namespaceName) {
            throw new UseStatementEqualsNamespaceException(
                'Can not add use statement with same name as containing namespace'
            );
        }

        $parts = explode('\\', $name);

        array_pop($parts);

        $prefixOfName = implode('\\', $parts);

        if ($prefixOfName === $namespaceName) {
            throw new UseStatementUnnecessaryException(
                'Can not add use statement with same name as containing namespace'
            );
        }
    }

    /**
     * @param string           $name
     * @param string           $kind
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UseStatementInsertionCreationException
     *
     * @return int
     */
    private function locateBestZeroIndexedLineForInsertion(
        string $name,
        string $kind,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): int {
        $bestLine = null;
        $previousMatchThatSharedNamespacePrefixLine = null;

        foreach ($this->retrieveRelevantUseStatements($textDocumentItem, $position) as $useStatement) {
            $groupOrderMap = [
                UseStatementKind::TYPE_CLASSLIKE => 0,
                UseStatementKind::TYPE_FUNCTION  => 1,
                UseStatementKind::TYPE_CONSTANT  => 2,
            ];

            // Don't analyze nodes of other types, but still ensure that we sort use statements into "groups" where
            // all use statements of a single kind are grouped together.
            if ($groupOrderMap[$kind] >= $groupOrderMap[$this->getUseStatementNodeKind($useStatement)]) {
                $bestLine = $useStatement->getEndLine();
            } elseif ($bestLine === null) {
                $bestLine = $useStatement->getStartLine() - 2;
            }

            if (!$this->doesUseStatementNodeHaveKind($useStatement, $kind)) {
                continue;
            }

            foreach ($useStatement->uses as $useUseNode) {
                $useUseNodeName = $this->getFullNameFromUseUse($useStatement, $useUseNode);

                if ($this->shouldNameBeSortedHigherThan($name, $useUseNodeName)) {
                    if ($previousMatchThatSharedNamespacePrefixLine !== null) {
                        $bestLine = $previousMatchThatSharedNamespacePrefixLine + 1;
                    }

                    break 2;
                }

                $previousMatchThatSharedNamespacePrefixLine =
                    $this->doShareCommonNamespacePrefix($name, $useUseNodeName) ?
                        $useStatement->getEndLine() :
                        null;
            }
        }

        if ($bestLine !== null) {
            return --$bestLine; // Make line zero-indexed.
        }

        return $this->determineZeroIndexedFallbackLine($textDocumentItem, $position);
    }

    /**
     * @param string           $name
     * @param string           $kind
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UseStatementInsertionCreationException
     *
     * @return bool
     */
    private function shouldAddAdditionalNewline(
        string $name,
        string $kind,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): bool {
        $addAdditionalNewline = true;
        $previousMatchThatSharedNamespacePrefixLine = null;

        foreach ($this->retrieveRelevantUseStatements($textDocumentItem, $position) as $useStatement) {
            if (!$this->doesUseStatementNodeHaveKind($useStatement, $kind)) {
                continue;
            }

            $addAdditionalNewline = true;

            foreach ($useStatement->uses as $useUseNode) {
                $useUseNodeName = $this->getFullNameFromUseUse($useStatement, $useUseNode);

                $addAdditionalNewline = !$this->doShareCommonNamespacePrefix($name, $useUseNodeName);

                if ($this->shouldNameBeSortedHigherThan($name, $useUseNodeName)) {
                    if ($previousMatchThatSharedNamespacePrefixLine !== null) {
                        $addAdditionalNewline = false;
                    }

                    break 2;
                }

                $previousMatchThatSharedNamespacePrefixLine =
                    $this->doShareCommonNamespacePrefix($name, $useUseNodeName) ?
                        $useStatement->getEndLine() :
                        null;
            }
        }

        return $addAdditionalNewline;
    }

    /**
     * @param Node\Stmt\Use_|Node\Stmt\GroupUse $use
     * @param string                            $kind
     *
     * @return bool
     */
    private function doesUseStatementNodeHaveKind(Node $use, string $kind): bool
    {
        return $this->getUseStatementNodeKind($use) === $kind;
    }

    /**
     * @param Node\Stmt\Use_|Node\Stmt\GroupUse $use
     *
     * @return string
     */
    private function getUseStatementNodeKind(Node $use): string
    {
        $map = [
            Use_::TYPE_UNKNOWN  => UseStatementKind::TYPE_CLASSLIKE,
            Use_::TYPE_NORMAL   => UseStatementKind::TYPE_CLASSLIKE,
            Use_::TYPE_FUNCTION => UseStatementKind::TYPE_FUNCTION,
            Use_::TYPE_CONSTANT => UseStatementKind::TYPE_CONSTANT,
        ];

        return $map[$use->type];
    }

    /**
     * @param string           $name
     * @param string           $kind
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UseStatementInsertionCreationException
     *
     * @return bool
     */
    private function shouldInsertBelowLineOfBestMatch(
        string $name,
        string $kind,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): bool {
        $useStatements = $this->retrieveRelevantUseStatements($textDocumentItem, $position);

        if (count($useStatements) === 0) {
            return false;
        }

        foreach ($useStatements as $useStatement) {
            if (!$this->doesUseStatementNodeHaveKind($useStatement, $kind)) {
                continue;
            }

            foreach ($useStatement->uses as $useUseNode) {
                $useUseNodeName = $this->getFullNameFromUseUse($useStatement, $useUseNode);

                if ($this->shouldNameBeSortedHigherThan($name, $useUseNodeName)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UseStatementInsertionCreationException
     *
     * @return (Node\Stmt\Use_|Node\Stmt\GroupUse)[]
     */
    private function retrieveRelevantUseStatements(TextDocumentItem $textDocumentItem, Position $position): array
    {
        $namespaceNode = $this->locateActiveNamespaceAt($textDocumentItem, $position);

        if ($namespaceNode !== null) {
            return $this->collectUseStatementsFromNamespaceNode($namespaceNode);
        }

        return $this->collectUseStatementsFromCode($textDocumentItem->getText());
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UseStatementInsertionCreationException
     *
     * @return int
     */
    private function determineZeroIndexedFallbackLine(TextDocumentItem $textDocumentItem, Position $position): int
    {
        $namespaceNode = $this->locateActiveNamespaceAt($textDocumentItem, $position);

        if ($namespaceNode !== null) {
            if ($namespaceNode->name !== null) {
                return $namespaceNode->name->getEndLine() + 2 - 1;
            } else {
                return $namespaceNode->getStartLine() + 1 - 1;
            }
        }

        $nodes = $this->getNodesFromCode($textDocumentItem->getText());

        if (count($nodes) > 0) {
            return max($nodes[0]->getStartLine() - 1 - 1, 0);
        }

        return 2;
    }

    /**
     * @param string           $name
     * @param string           $kind
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UseStatementInsertionCreationException
     *
     * @return bool
     */
    private function isUseStatementAlreadyPresent(
        string $name,
        string $kind,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): bool {
        $useStatements = $this->retrieveRelevantUseStatements($textDocumentItem, $position);

        return count(array_filter(
            $useStatements,
            function (Node\Stmt $useStatement) use ($name, $kind): bool {
                /** @var Node\Stmt\Use_|Node\Stmt\GroupUse $useStatement */
                $useStatement = $useStatement;

                if ($this->getUseStatementNodeKind($useStatement) !== $kind) {
                    return false;
                }

                foreach ($useStatement->uses as $useUseNode) {
                    if ($this->getFullNameFromUseUse($useStatement, $useUseNode) === $name) {
                        return true;
                    }
                }

                return false;
            }
        )) > 0;
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
        }

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
     * @return int
     */
    private function scoreClassName(string $firstClassName, string $secondClassName): int
    {
        $firstClassNameParts = array_values(array_filter(explode('\\', $firstClassName)));
        $secondClassNameParts = array_values(array_filter(explode('\\', $secondClassName)));

        $maxLength = min(count($firstClassNameParts), count($secondClassNameParts));

        // Always sort unqualified imports before everything else.
        if (count($firstClassNameParts) !== count($secondClassNameParts)) {
            if (count($firstClassNameParts) === 1) {
                return -1;
            } elseif (count($secondClassNameParts) === 1) {
                return 1;
            }
        }

        for ($i = 0; $i < $maxLength; ++$i) {
            if ($firstClassNameParts[$i] === $secondClassNameParts[$i]) {
                continue;
            } elseif (mb_strlen($firstClassNameParts[$i]) !== mb_strlen($secondClassNameParts[$i]) &&
                count($firstClassNameParts) === count($secondClassNameParts) &&
                $i === $maxLength - 1
            ) {
                // For use statements that only differ in the last segment (with a common namespace segment),
                // sort the last part by length so we get a neat gradually expanding half of a christmas tree.
                return mb_strlen($firstClassNameParts[$i]) <=> mb_strlen($secondClassNameParts[$i]);
            }

            $result = substr_compare($firstClassNameParts[$i], $secondClassNameParts[$i], 0);

            return $result !== false ? $result : 0;
        }

        return count($firstClassNameParts) <=> count($secondClassNameParts);
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @throws UseStatementInsertionCreationException
     *
     * @return Node\Stmt\Namespace_|null
     */
    private function locateActiveNamespaceAt(
        TextDocumentItem $textDocumentItem,
        Position $position
    ): ?Node\Stmt\Namespace_ {
        $result = $this->nodeAtOffsetLocator->locate($textDocumentItem, $position);

        $node = $result->getNode();

        while ($node !== null) {
            if ($node instanceof Node\Stmt\Namespace_) {
                return $node;
            }

            $node = $node->getAttribute('parent', false);

            if ($node === false) {
                throw new LogicException('No required parent metadata attached to node');
            }
        }

        $nodes = [];

        try {
            $nodes = $this->getNodesFromCode($textDocumentItem->getText());
        } catch (UnexpectedValueException $e) {
            throw new UseStatementInsertionCreationException(
                'Could not parse code needed for use statement insertion creation',
                0,
                $e
            );
        }

        foreach ($nodes as $codeNode) {
            $endFilePos = $codeNode->getAttribute('endFilePos');
            $startFilePos = $codeNode->getAttribute('startFilePos');

            $byteOffset = $position->getAsByteOffsetInString($textDocumentItem->getText(), PositionEncoding::VALUE);

            if ($startFilePos > $byteOffset) {
                break;
            } elseif ($codeNode instanceof Node\Stmt\Namespace_) {
                return $codeNode;
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
