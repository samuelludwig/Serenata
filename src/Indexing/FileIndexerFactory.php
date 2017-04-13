<?php

namespace PhpIntegrator\Indexing;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Analysis\Typing\Resolving\TypeResolverInterface;

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
     * @var TypeResolverInterface
     */
    private $typeResolver;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @param StorageInterface         $storage
     * @param DocblockParser           $docblockParser
     * @param TypeAnalyzer             $typeAnalyzer
     * @param TypeResolverInterface    $typeResolver
     * @param Parser                   $parser
     * @param NodeTypeDeducerInterface $nodeTypeDeducer
     */
    public function __construct(
        StorageInterface $storage,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        TypeResolverInterface $typeResolver,
        Parser $parser,
        NodeTypeDeducerInterface $nodeTypeDeducer
    ) {
        $this->storage = $storage;
        $this->docblockParser = $docblockParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->typeResolver = $typeResolver;
        $this->parser = $parser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
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
            $this->typeResolver,
            $this->docblockParser,
            $this->nodeTypeDeducer,
            $this->parser
        );
    }
}
