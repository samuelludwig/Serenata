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
        $useStatementList = [];
        $namespaceNode = $this->locateActiveNamespaceAt($code, $position);

        if ($namespaceNode !== null) {
            $useStatementList = $this->collectUseStatementsFromNamespaceNode($namespaceNode);
        } else {
            $useStatementList = $this->collectUseStatementsFromCode($code);
        }

        if ($this->isUseStatementPresentInList($name, $kind, $useStatementList)) {
            throw new UseStatementAlreadyExistsException(
                'Use statement for ' . $name . ' with kind ' . $kind . 'already exists'
            );
        }

        // TODO: Automatically select active namespace node based on $offset and insert use statement there (see test
        // stub)
        // TODO: Handle corner case:
        //    # When we have no namespace or are in an anonymous namespace, adding use statements for "non-compound"
        //    # namespaces, such as "DateTime" will generate a warning.
        // TODO: Adapt legacy code:
        //     else if suggestion.data.nameToImport.indexOf(currentNamespaceName) == 0
        //          nameToImportRelativeToNamespace = suggestion.displayText.substr(currentNamespaceName.length + 1)
        //
        //          # If a user is in A\B and wants to import A\B\C\D, we don't need to add a use statement if he is typing
        //          # C\D, as it will be relative, but we will need to add one when he typed just D as it won't be
        //          # relative.
        //          return if nameToImportRelativeToNamespace.split('\\').length == suggestion.text.split('\\').length
        // TODO: Refactor entire class, mostly just a direct translation of the CoffeeScript code from the Atom package.

        return $this->locateInsertionPosition(
            $name,
            $kind,
            $code,
            $position,
            $useStatementList,
            $allowAdditionalNewlines
        );

        // return new TextEdit(
        //     $this->locateInsertionPosition($name, $kind, $useStatementList, $allowAdditionalNewlines),
        //     $this->getInsertionText($name, $kind)
        // );
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
            return $namespaceNode->getEndLine() + 2 - 1;
        }

        $nodes = $this->getNodesFromCode($code);

        if (!empty($nodes)) {
            return max($nodes[0]->getStartLine() - 1 - 1, 0);
        }

        return 2;
    }

    /**
     * @param string                                $name
     * @param string                                $kind
     * @param string                                $code
     * @param int                                   $position
     * @param (Node\Stmt\Use_|Node\Stmt\GroupUse)[] $useStatementList
     * @param bool                                  $allowAdditionalNewlines
     *
     * @return TextEdit
     */
    private function locateInsertionPosition(
        string $name,
        string $kind,
        string $code,
        int $position,
        array $useStatementList,
        bool $allowAdditionalNewlines
    ): TextEdit {
        $placeBelow = false;
        $doNewLine = false;
        $bestUseStatementLine = null;
        $lastMatchThatSharedNamespacePrefixLine = null;

        $normalizedName = $this->typeNormalizer->getNormalizedFqcn($name);

        foreach ($useStatementList as $useStatement) {
            $placeBelow = true;
            $bestUseStatementLine = $useStatement->getEndLine();

            foreach ($useStatement->uses as $useUseNode) {
                $useUseNodeName = $this->getFullNameFromUseUse($useStatement, $useUseNode);

                $shareCommonNamespacePrefix = $this->doShareCommonNamespacePrefix($normalizedName, $useUseNodeName);

                $doNewLine = !$shareCommonNamespacePrefix;

                if ($this->scoreClassName($normalizedName, $useUseNodeName) <= 0) {
                    $placeBelow = false;

                    // Normally we keep going until the sorting indicates we should stop, and then place the use
                    // statement above the 'incorrect' match, but if the previous use statement was a use statement
                    // that has the same namespace, we want to ensure we stick close to it instead of creating
                    // additional newlines (which the item from the same namespace already placed).
                    if ($lastMatchThatSharedNamespacePrefixLine !== null) {
                        $placeBelow = true;
                        $doNewLine = false;
                        $bestUseStatementLine = $lastMatchThatSharedNamespacePrefixLine;
                    }

                    break 2;
                }

                $lastMatchThatSharedNamespacePrefixLine = $shareCommonNamespacePrefix !== null ?
                    $useStatement->getEndLine() :
                    null;
            }
        }

        if ($bestUseStatementLine === null) {
            $bestUseStatementLine = $this->determineZeroIndexedFallbackLine($code, $position);
        } else {
            --$bestUseStatementLine; // Make line zero-indexed.
        }

        if (!$allowAdditionalNewlines) {
            $doNewLine = false;
        }

        $textToInsert = '';

        if ($doNewLine && $placeBelow) {
            $textToInsert .= "\n";
        }

        $textToInsert .= "use {$name};\n";

        if ($doNewLine && !$placeBelow) {
            $textToInsert .= "\n";
        }

        $line = $bestUseStatementLine + ($placeBelow ? 1 : 0);

        $range = new Range(new Position($line, 0), new Position($line, 0));

        return new TextEdit($range, $textToInsert);
    }

    // /**
    //  * @param string $name
    //  * @param string $kind
    //  *
    //  * @return string
    //  */
    // private function getInsertionText(string $name, string $kind): string
    // {
    //     return "use {$name};\n";
    // }

    /**
     * @param string                                $name
     * @param string                                $kind
     * @param (Node\Stmt\Use_|Node\Stmt\GroupUse)[] $list
     *
     * @return bool
     */
    private function isUseStatementPresentInList(string $name, string $kind, array $list): bool
    {
        $normalizedName = $this->typeNormalizer->getNormalizedFqcn($name);

        return !empty(array_filter($list, function (Node\Stmt $useStatement) use ($normalizedName): bool {
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

        // At this point, both FQSEN's share a common namespace, e.g. A\B and A\B\C\D, or XMLElement and XMLDocument.
        // The one with the most namespace parts ends up last.
        if (count($firstClassNameParts) < count($secondClassNameParts)) {
            return -1;
        } elseif (count($firstClassNameParts) > count($secondClassNameParts)) {
            return 1;
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

            $parentNode = $node->getAttribute('parent', false);

            if ($parentNode === false) {
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

    /*
    ###*
     * Add the use for the given class if not already added.
     *
     * @param {TextEditor} editor    Atom text editor.
     * @param {String}     className Name of the class to add.
     *
     * @return {Number} The amount of lines added (including newlines), so you can reliably and easily offset your rows.
     #                  This could be zero if a use statement was already present.
    ###
    addUseClass: (editor, className) ->
        bestUseRow = 0
        placeBelow = true
        doNewLine = true
        lineCount = editor.getLineCount()
        previousMatchThatSharedNamespacePrefixRow = null

        # First see if the use statement is already present. The next loop stops early (and can't do this).
        for i in [0 .. lineCount - 1]
            line = editor.lineTextForBufferRow(i).trim()

            continue if line.length == 0

            scopeDescriptor = editor.scopeDescriptorForBufferPosition([i, line.length]).getScopeChain()

            if scopeDescriptor.indexOf('.comment') >= 0
                continue

            break if line.match(@structureStartRegex)

            if (matches = @useStatementRegex.exec(line))
                if matches[1] == className or (matches[1][0] == '\\' and matches[1].substr(1) == className)
                    return 0

        # Determine an appropriate location to place the use statement.
        for i in [0 .. lineCount - 1]
            line = editor.lineTextForBufferRow(i).trim()

            continue if line.length == 0

            scopeDescriptor = editor.scopeDescriptorForBufferPosition([i, line.length]).getScopeChain()

            if scopeDescriptor.indexOf('.comment') >= 0
                continue

            break if line.match(@structureStartRegex)

            if line.indexOf('namespace ') >= 0
                bestUseRow = i

            if (matches = @useStatementRegex.exec(line))
                bestUseRow = i

                placeBelow = true
                shareCommonNamespacePrefix = @doShareCommonNamespacePrefix(className, matches[1])

                doNewLine = not shareCommonNamespacePrefix

                if @scoreClassName(className, matches[1]) <= 0
                    placeBelow = false

                    # Normally we keep going until the sorting indicates we should stop, and then place the use
                    # statement above the 'incorrect' match, but if the previous use statement was a use statement
                    # that has the same namespace, we want to ensure we stick close to it instead of creating additional
                    # newlines (which the item from the same namespace already placed).
                    if previousMatchThatSharedNamespacePrefixRow?
                        placeBelow = true
                        doNewLine = false
                        bestUseRow = previousMatchThatSharedNamespacePrefixRow

                    break

                previousMatchThatSharedNamespacePrefixRow = if shareCommonNamespacePrefix then i else null

        # Insert the use statement itself.
        lineEnding = editor.getBuffer().lineEndingForRow(0)

        if not @allowAdditionalNewlines
            doNewLine = false

        if not lineEnding
            lineEnding = "\n"

        textToInsert = ''

        if doNewLine and placeBelow
            textToInsert += lineEnding

        textToInsert += "use #{className};" + lineEnding

        if doNewLine and not placeBelow
            textToInsert += lineEnding

        lineToInsertAt = bestUseRow + (if placeBelow then 1 else 0)
        editor.setTextInBufferRange([[lineToInsertAt, 0], [lineToInsertAt, 0]], textToInsert)

        return (1 + (if doNewLine then 1 else 0))

    ###*
     * Returns a boolean indicating if the specified class names share a common namespace prefix.
     *
     * @param {String} firstClassName
     * @param {String} secondClassName
     *
     * @return {Boolean}
    ###
    doShareCommonNamespacePrefix: (firstClassName, secondClassName) ->
        firstClassNameParts = firstClassName.split('\\')
        secondClassNameParts = secondClassName.split('\\')

        firstClassNameParts.pop()
        secondClassNameParts.pop()

        return if firstClassNameParts.join('\\') == secondClassNameParts.join('\\') then true else false

    ###*
     * Scores the first class name against the second, indicating how much they 'match' each other. This can be used
     * to e.g. find an appropriate location to place a class in an existing list of classes.
     *
     * @param {String} firstClassName
     * @param {String} secondClassName
     *
     * @return {Number} A floating point number that represents the score.
    ###
    scoreClassName: (firstClassName, secondClassName) ->
        maxLength = 0
        totalScore = 0

        firstClassNameParts = firstClassName.split('\\')
        secondClassNameParts = secondClassName.split('\\')

        maxLength = Math.min(firstClassNameParts.length, secondClassNameParts.length)

        collator = new Intl.Collator

        if maxLength >= 2
            for i in [0 .. maxLength - 2]
                if firstClassNameParts[i] != secondClassNameParts[i]
                    return collator.compare(firstClassNameParts[i], secondClassNameParts[i])

        # At this point, both FQSEN's share a common namespace, e.g. A\B and A\B\C\D, or XMLElement and XMLDocument.
        # The one with the most namespace parts ends up last.
        if firstClassNameParts.length > secondClassNameParts.length
            return 1

        else if firstClassNameParts.length < secondClassNameParts.length
            return -1

        if firstClassName.length == secondClassName.length
            return collator.compare(firstClassName, secondClassName)

        # Both items have share the same namespace, sort from shortest to longest last word (class, interface, ...).
        return firstClassName.length > secondClassName.length ? 1 : -1
    */
}
