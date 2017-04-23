<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpIntegrator\Analysis\Conversion\ConstantConverter;

use PhpIntegrator\Common\Position;
use PhpIntegrator\Common\FilePosition;

use PhpIntegrator\Indexing\IndexDatabase;

use PhpIntegrator\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use PhpIntegrator\Utility\NodeHelpers;
use PhpIntegrator\Utility\SourceCodeHelpers;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\ConstFetch} node.
 */
class ConstFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @var IndexDatabase
     */
    private $indexDatabase;

    /**
     * @var ConstantConverter
     */
    private $constantConverter;

    /**
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param IndexDatabase                    $indexDatabase
     * @param ConstantConverter                $constantConverter
     */
    public function __construct(
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        IndexDatabase $indexDatabase,
        ConstantConverter $constantConverter
    ) {
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->indexDatabase = $indexDatabase;
        $this->constantConverter = $constantConverter;
    }

    /**
     * @inheritDoc
     */
    public function deduce(Node $node, string $file, string $code, int $offset): array
    {
        if (!$node instanceof Node\Expr\ConstFetch) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromConstFetchNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\ConstFetch $node
     * @param string               $file
     * @param string               $code
     * @param int                  $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromConstFetchNode(
        Node\Expr\ConstFetch $node,
        string $file,
        string $code,
        int $offset
    ): array {
        $name = NodeHelpers::fetchClassName($node->name);

        if ($name === 'null') {
            return ['null'];
        } elseif ($name === 'true' || $name === 'false') {
            return ['bool'];
        }

        $filePosition = new FilePosition(
            $file,
            new Position(SourceCodeHelpers::calculateLineByOffset($code, $offset), 0)
        );

        $fqcn = $this->structureAwareNameResolverFactory->create($filePosition)->resolve($name, $filePosition);

        $globalConstant = $this->indexDatabase->getGlobalConstantByFqcn($fqcn);

        if (!$globalConstant) {
            return [];
        }

        $convertedGlobalConstant = $this->constantConverter->convert($globalConstant);

        return $this->fetchResolvedTypesFromTypeArrays($convertedGlobalConstant['types']);
    }
}
