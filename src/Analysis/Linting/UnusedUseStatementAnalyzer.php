<?php

namespace PhpIntegrator\Analysis\Linting;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;
use PhpIntegrator\Analysis\Visiting\NamespaceAttachingVisitor;
use PhpIntegrator\Analysis\Visiting\ClassUsageFetchingVisitor;
use PhpIntegrator\Analysis\Visiting\UseStatementFetchingVisitor;
use PhpIntegrator\Analysis\Visiting\DocblockClassUsageFetchingVisitor;
use PhpIntegrator\Analysis\Visiting\GlobalFunctionUsageFetchingVisitor;
use PhpIntegrator\Analysis\Visiting\GlobalConstantUsageFetchingVisitor;

use PhpIntegrator\Parsing\DocblockParser;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Utility\NodeHelpers;

/**
 * Looks for unused use statements.
 */
class UnusedUseStatementAnalyzer implements AnalyzerInterface
{
    /**
     * @var NamespaceAttachingVisitor
     */
    protected $namespaceAttachingVisitor;

    /**
     * @var ClassUsageFetchingVisitor
     */
    protected $classUsageFetchingVisitor;

    /**
     * @var UseStatementFetchingVisitor
     */
    protected $useStatementFetchingVisitor;

    /**
     * @var GlobalConstantUsageFetchingVisitor
     */
    protected $globalConstantUsageFetchingVisitor;

    /**
     * @var GlobalFunctionUsageFetchingVisitor
     */
    protected $globalFunctionUsageFetchingVisitor;

    /**
     * @var DocblockClassUsageFetchingVisitor
     */
    protected $docblockClassUsageFetchingVisitor;

    /**
     * Constructor.
     *
     * @param TypeAnalyzer   $typeAnalyzer
     * @param DocblockParser $docblockParser
     */
    public function __construct(TypeAnalyzer $typeAnalyzer, DocblockParser $docblockParser)
    {
        $this->namespaceAttachingVisitor = new NamespaceAttachingVisitor();
        $this->classUsageFetchingVisitor = new ClassUsageFetchingVisitor($typeAnalyzer);
        $this->useStatementFetchingVisitor = new UseStatementFetchingVisitor();
        $this->globalConstantUsageFetchingVisitor = new GlobalConstantUsageFetchingVisitor();
        $this->globalFunctionUsageFetchingVisitor = new GlobalFunctionUsageFetchingVisitor();
        $this->docblockClassUsageFetchingVisitor = new DocblockClassUsageFetchingVisitor($typeAnalyzer, $docblockParser);
    }

    /**
     * @inheritDoc
     */
    public function getVisitors(): array
    {
        return [
            $this->namespaceAttachingVisitor,
            $this->classUsageFetchingVisitor,
            $this->useStatementFetchingVisitor,
            $this->docblockClassUsageFetchingVisitor,
            $this->globalConstantUsageFetchingVisitor,
            $this->globalFunctionUsageFetchingVisitor
        ];
    }

    /**
     * @inheritDoc
     */
    public function getOutput(): array
    {
        $unusedUseStatements = array_merge(
            $this->getOutputForClasses(),
            $this->getOutputForConstants(),
            $this->getOutputForFunctions()
        );

        return $unusedUseStatements;
    }

    /**
     * @return array
     */
    protected function getOutputForClasses(): array
    {
        // Cross-reference the found class names against the class map.
        $namespaces = $this->useStatementFetchingVisitor->getNamespaces();

        $classUsages = array_merge(
            $this->classUsageFetchingVisitor->getClassUsageList(),
            $this->docblockClassUsageFetchingVisitor->getClassUsageList()
        );

        foreach ($classUsages as $classUsage) {
            $relevantAlias = $classUsage['firstPart'];

            if (!$classUsage['isFullyQualified'] &&
                isset($namespaces[$classUsage['namespace']]['useStatements'][$relevantAlias]) &&
                $namespaces[$classUsage['namespace']]['useStatements'][$relevantAlias]['kind'] === UseStatementKind::TYPE_CLASSLIKE
            ) {
                // Mark the accompanying used statement, if any, as used.
                $namespaces[$classUsage['namespace']]['useStatements'][$relevantAlias]['used'] = true;
            }
        }

        $unusedUseStatements = [];

        foreach ($namespaces as $namespace => $namespaceData) {
            $useStatementMap = $namespaceData['useStatements'];

            foreach ($useStatementMap as $alias => $data) {
                if (
                    (!array_key_exists('used', $data) || !$data['used']) &&
                    $data['kind'] === UseStatementKind::TYPE_CLASSLIKE
                ) {
                    unset($data['line'], $data['kind']);

                    $unusedUseStatements[] = $data;
                }
            }
        }

        return $unusedUseStatements;
    }

    /**
     * @return array
     */
    protected function getOutputForConstants(): array
    {
        $unknownClasses = [];
        $namespaces = $this->useStatementFetchingVisitor->getNamespaces();

        $constantUsages = $this->globalConstantUsageFetchingVisitor->getGlobalConstantList();

        foreach ($constantUsages as $node) {
            $relevantAlias = $node->name->getFirst();

            $namespaceNode = $node->getAttribute('namespace');
            $namespace = null;

            if ($namespaceNode !== null) {
                $namespace = NodeHelpers::fetchClassName($namespaceNode);
            }

            if (!$node->name->isFullyQualified() &&
                isset($namespaces[$namespace]['useStatements'][$relevantAlias]) &&
                $namespaces[$namespace]['useStatements'][$relevantAlias]['kind'] === UseStatementKind::TYPE_CONSTANT
            ) {
                // Mark the accompanying used statement, if any, as used.
                $namespaces[$namespace]['useStatements'][$relevantAlias]['used'] = true;
            }
        }

        $unusedUseStatements = [];

        foreach ($namespaces as $namespace => $namespaceData) {
            $useStatementMap = $namespaceData['useStatements'];

            foreach ($useStatementMap as $alias => $data) {
                if (
                    (!array_key_exists('used', $data) || !$data['used']) &&
                    $data['kind'] === UseStatementKind::TYPE_CONSTANT
                ) {
                    unset($data['line'], $data['kind']);

                    $unusedUseStatements[] = $data;
                }
            }
        }

        return $unusedUseStatements;
    }

    /**
     * @return array
     */
    protected function getOutputForFunctions(): array
    {
        $unknownClasses = [];
        $namespaces = $this->useStatementFetchingVisitor->getNamespaces();

        $functionUsages = $this->globalFunctionUsageFetchingVisitor->getGlobalFunctionCallList();

        foreach ($functionUsages as $node) {
            $relevantAlias = $node->name->getFirst();
            $namespaceNode = $node->getAttribute('namespace');
            $namespace = null;

            if ($namespaceNode !== null) {
                $namespace = NodeHelpers::fetchClassName($namespaceNode);
            }

            if (!$node->name->isFullyQualified() &&
                isset($namespaces[$namespace]['useStatements'][$relevantAlias]) &&
                $namespaces[$namespace]['useStatements'][$relevantAlias]['kind'] === UseStatementKind::TYPE_FUNCTION
            ) {
                // Mark the accompanying used statement, if any, as used.
                $namespaces[$namespace]['useStatements'][$relevantAlias]['used'] = true;
            }
        }

        $unusedUseStatements = [];

        foreach ($namespaces as $namespace => $namespaceData) {
            $useStatementMap = $namespaceData['useStatements'];

            foreach ($useStatementMap as $alias => $data) {
                if (
                    (!array_key_exists('used', $data) || !$data['used']) &&
                    $data['kind'] === UseStatementKind::TYPE_FUNCTION
                ) {
                    unset($data['line'], $data['kind']);

                    $unusedUseStatements[] = $data;
                }
            }
        }

        return $unusedUseStatements;
    }
}
