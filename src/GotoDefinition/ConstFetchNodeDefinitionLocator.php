<?php

namespace PhpIntegrator\GotoDefinition;

use UnexpectedValueException;

use PhpIntegrator\Analysis\ConstantListProviderInterface;

use PhpIntegrator\Analysis\Node\ConstNameNodeFqsenDeterminer;

use PhpParser\Node;

/**
 * Locates the definition of the constant called in {@see Node\Expr\ConstFetch} nodes.
 */
class ConstFetchNodeDefinitionLocator
{
    /**
     * @var ConstNameNodeFqsenDeterminer
     */
    private $constFetchNodeFqsenDeterminer;

    /**
     * @var ConstantListProviderInterface
     */
    private $constantListProvider;

    /**
     * @param ConstNameNodeFqsenDeterminer  $constFetchNodeFqsenDeterminer
     * @param ConstantListProviderInterface $constantListProvider
     */
    public function __construct(
        ConstNameNodeFqsenDeterminer $constFetchNodeFqsenDeterminer,
        ConstantListProviderInterface $constantListProvider
    ) {
        $this->constFetchNodeFqsenDeterminer = $constFetchNodeFqsenDeterminer;
        $this->constantListProvider = $constantListProvider;
    }

    /**
     * @param Node\Expr\ConstFetch $node
     *
     * @throws UnexpectedValueException when the constant was not found.
     *
     * @return GotoDefinitionResult
     */
    public function generate(Node\Expr\ConstFetch $node): GotoDefinitionResult
    {
        $fqsen = $this->constFetchNodeFqsenDeterminer->determine($node->name);

        $info = $this->getConstantInfo($fqsen);

        return new GotoDefinitionResult($info['filename'], $info['startLine']);
    }

    /**
     * @param string $fullyQualifiedName
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    private function getConstantInfo(string $fullyQualifiedName): array
    {
        $functions = $this->constantListProvider->getAll();

        if (!isset($functions[$fullyQualifiedName])) {
            throw new UnexpectedValueException('No data found for function with name ' . $fullyQualifiedName);
        }

        return $functions[$fullyQualifiedName];
    }
}
