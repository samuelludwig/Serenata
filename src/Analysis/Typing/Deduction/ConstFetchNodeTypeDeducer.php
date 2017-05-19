<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpIntegrator\Analysis\Conversion\ConstantConverter;

use PhpIntegrator\Common\Position;
use PhpIntegrator\Common\FilePosition;

use PhpIntegrator\Indexing\ManagerRegistry;

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
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var ConstantConverter
     */
    private $constantConverter;

    /**
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param ManagerRegistry                            $managerRegistry
     * @param ConstantConverter                          $constantConverter
     */
    public function __construct(
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        ManagerRegistry $managerRegistry,
        ConstantConverter $constantConverter
    ) {
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->managerRegistry = $managerRegistry;
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

        $globalConstant = $this->managerRegistry->getRepository(Structures\Constant::class)->findOneBy([
            'fqcn' => $fqcn
        ]);

        if (!$globalConstant) {
            return [];
        }

        $convertedGlobalConstant = $this->constantConverter->convert($globalConstant);

        return $this->fetchResolvedTypesFromTypeArrays($convertedGlobalConstant['types']);
    }
}
