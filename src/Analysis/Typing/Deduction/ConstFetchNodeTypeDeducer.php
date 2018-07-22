<?php

namespace Serenata\Analysis\Typing\Deduction;

use UnexpectedValueException;

use Serenata\Utility\PositionEncoding;

use Serenata\Analysis\Conversion\ConstantConverter;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Utility\NodeHelpers;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\ConstFetch} node.
 */
final class ConstFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
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
    public function deduce(Node $node, Structures\File $file, string $code, int $offset): array
    {
        if (!$node instanceof Node\Expr\ConstFetch) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromConstFetchNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\ConstFetch $node
     * @param Structures\File      $file
     * @param string               $code
     * @param int                  $offset
     *
     * @return string[]
     */
    private function deduceTypesFromConstFetchNode(
        Node\Expr\ConstFetch $node,
        Structures\File $file,
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
            $file->getPath(),
            Position::createFromByteOffset($offset, $code, PositionEncoding::VALUE)
        );

        $fqsen = $this->structureAwareNameResolverFactory->create($filePosition)->resolve($name, $filePosition);

        $globalConstant = $this->managerRegistry->getRepository(Structures\Constant::class)->findOneBy([
            'fqcn' => $fqsen
        ]);

        if (!$globalConstant) {
            return [];
        }

        $convertedGlobalConstant = $this->constantConverter->convert($globalConstant);

        return $this->fetchResolvedTypesFromTypeArrays($convertedGlobalConstant['types']);
    }
}
