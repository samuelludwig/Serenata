<?php

namespace PhpIntegrator\Indexing;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use PhpIntegrator\Parsing\DocblockParser;

use PhpParser\Parser;

/**
 * Returns an appropriate file indexer for the specified file.
 */
class FileIndexerFactory implements FileIndexerFactoryInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DocblockParser
     */
    private $docblockParser;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @param StorageInterface                           $storage
     * @param DocblockParser                             $docblockParser
     * @param TypeAnalyzer                               $typeAnalyzer
     * @param Parser                                     $parser
     * @param NodeTypeDeducerInterface                   $nodeTypeDeducer
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     */
    public function __construct(
        StorageInterface $storage,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        Parser $parser,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
    ) {
        $this->storage = $storage;
        $this->docblockParser = $docblockParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->parser = $parser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
    }

    /**
     * @inheritDoc
     */
    public function create(string $filePath): FileIndexerInterface
    {
        $pathInfo = pathinfo($filePath);

        if ($pathInfo['basename'] === '.phpstorm.meta.php') {
            return new MetaFileIndexer($this->storage, $this->parser);
        }

        return new FileIndexer(
            $this->storage,
            $this->typeAnalyzer,
            $this->docblockParser,
            $this->nodeTypeDeducer,
            $this->parser,
            $this->structureAwareNameResolverFactory
        );
    }
}
