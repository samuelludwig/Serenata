<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\ClasslikeExistenceCheckerInterface;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverInterface;

use PhpIntegrator\Analysis\Visiting\ClassUsageFetchingVisitor;
use PhpIntegrator\Analysis\Visiting\DocblockClassUsageFetchingVisitor;

use PhpIntegrator\Parsing\DocblockParser;

/**
 * Looks for unknown class names.
 */
class UnknownClassAnalyzer implements AnalyzerInterface
{
    /**
     * @var ClassUsageFetchingVisitor
     */
    private $classUsageFetchingVisitor;

    /**
     * @var DocblockClassUsageFetchingVisitor
     */
    private $docblockClassUsageFetchingVisitor;

    /**
     * @var ClasslikeExistenceCheckerInterface
     */
    private $classlikeExistenceChecker;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var FileTypeResolverInterface
     */
    private $fileTypeResolver;

    /**
     * Constructor.
     *
     * @param ClasslikeExistenceCheckerInterface $classlikeExistenceChecker
     * @param FileTypeResolverInterface          $fileTypeResolver
     * @param TypeAnalyzer                       $typeAnalyzer
     * @param DocblockParser                     $docblockParser
     */
    public function __construct(
        ClasslikeExistenceCheckerInterface $classlikeExistenceChecker,
        FileTypeResolverInterface $fileTypeResolver,
        TypeAnalyzer $typeAnalyzer,
        DocblockParser $docblockParser
    ) {
        $this->typeAnalyzer = $typeAnalyzer;
        $this->fileTypeResolver = $fileTypeResolver;
        $this->classlikeExistenceChecker = $classlikeExistenceChecker;

        $this->classUsageFetchingVisitor = new ClassUsageFetchingVisitor($typeAnalyzer);
        $this->docblockClassUsageFetchingVisitor = new DocblockClassUsageFetchingVisitor($typeAnalyzer, $docblockParser);
    }

    /**
     * @inheritDoc
     */
    public function getVisitors(): array
    {
        return [
            $this->classUsageFetchingVisitor,
            $this->docblockClassUsageFetchingVisitor
        ];
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        // Cross-reference the found class names against the class map.
        $unknownClasses = [];

        $classUsages = array_merge(
            $this->classUsageFetchingVisitor->getClassUsageList(),
            $this->docblockClassUsageFetchingVisitor->getClassUsageList()
        );

        foreach ($classUsages as $classUsage) {
            if ($classUsage['isFullyQualified']) {
                $fqcn = $classUsage['name'];
            } else {
                $fqcn = $this->fileTypeResolver->resolve($classUsage['name'], $classUsage['line']);
            }

            $fqcn = $this->typeAnalyzer->getNormalizedFqcn($fqcn);

            if (!$this->classlikeExistenceChecker->doesClassExist($fqcn)) {
                $unknownClasses[] = [
                    'message' => "Classlike is not defined or imported anywhere.",
                    'start'   => $classUsage['start'],
                    'end'     => $classUsage['end']
                ];
            }
        }

        return $unknownClasses;
    }

    /**
     * @inheritDoc
     */
    public function getWarnings(): array
    {
        return [];
    }
}
