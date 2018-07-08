<?php

namespace Serenata\Analysis\Typing\Deduction;

use UnexpectedValueException;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\TypeNormalizerInterface;
use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Common\Position;
use Serenata\Common\FilePosition;

use Serenata\Indexing\Structures;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Utility\NodeHelpers;
use Serenata\Utility\SourceCodeHelpers;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Name} node.
 */
final class NameNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var TypeNormalizerInterface
     */
    private $typeNormalizer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @var FileClasslikeListProviderInterface
     */
    private $fileClasslikeListProvider;

    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @param TypeNormalizerInterface                    $typeNormalizer
     * @param ClasslikeInfoBuilderInterface              $classlikeInfoBuilder
     * @param FileClasslikeListProviderInterface         $fileClasslikeListProvider
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     */
    public function __construct(
        TypeNormalizerInterface $typeNormalizer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        FileClasslikeListProviderInterface $fileClasslikeListProvider,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
    ) {
        $this->typeNormalizer = $typeNormalizer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->fileClasslikeListProvider = $fileClasslikeListProvider;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
    }

    /**
     * @inheritDoc
     */
    public function deduce(Node $node, Structures\File $file, string $code, int $offset): array
    {
        if (!$node instanceof Node\Name) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromNameNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Name       $node
     * @param Structures\File $file
     * @param string          $code
     * @param int             $offset
     *
     * @return string[]
     */
    private function deduceTypesFromNameNode(Node\Name $node, Structures\File $file, string $code, int $offset): array
    {
        $nameString = NodeHelpers::fetchClassName($node);

        if ($nameString === 'static' || $nameString === 'self') {
            $currentClass = $this->findCurrentClassAt($file, $code, $offset);

            if ($currentClass === null) {
                return [];
            }

            return [$this->typeNormalizer->getNormalizedFqcn($currentClass)];
        } elseif ($nameString === 'parent') {
            $currentClassName = $this->findCurrentClassAt($file, $code, $offset);

            if (!$currentClassName) {
                return [];
            }

            $classInfo = $this->classlikeInfoBuilder->build($currentClassName);

            if (!$classInfo || empty($classInfo['parents'])) {
                return [];
            }

            $type = $classInfo['parents'][0];

            return [$this->typeNormalizer->getNormalizedFqcn($type)];
        }

        $line = SourceCodeHelpers::calculateLineByOffset($code, $offset);

        $filePosition = new FilePosition(
            $file->getPath(),
            new Position($line, 0)
        );

        $fqcn = $this->structureAwareNameResolverFactory->create($filePosition)->resolve($nameString, $filePosition);

        return [$fqcn];
    }

    /**
     * @param Structures\File $file
     * @param string          $source
     * @param int             $offset
     *
     * @return string|null
     */
    private function findCurrentClassAt(Structures\File $file, string $source, int $offset): ?string
    {
        $line = SourceCodeHelpers::calculateLineByOffset($source, $offset);

        return $this->findCurrentClassAtLine($file, $source, $line);
    }

    /**
     * @param Structures\File $file
     * @param string          $source
     * @param int             $line
     *
     * @return string|null
     */
    private function findCurrentClassAtLine(Structures\File $file, string $source, int $line): ?string
    {
        $classes = $this->fileClasslikeListProvider->getAllForFile($file);

        foreach ($classes as $fqcn => $class) {
            if ($line >= $class['startLine'] && $line <= $class['endLine']) {
                return $fqcn;
            }
        }

        return null;
    }
}
