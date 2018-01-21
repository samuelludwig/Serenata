<?php

namespace PhpIntegrator\Indexing;

use DateTime;
use Exception;
use AssertionError;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use PhpIntegrator\Parsing\DocblockParser;

use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

/**
 * Handles indexation of PHP code in a single file.
 *
 * The index only contains "direct" data, meaning that it only contains data that is directly attached to an element.
 * For example, classes will only have their direct members attached in the index. The index will also keep track of
 * links between structural elements and parents, implemented interfaces, and more, but it will not duplicate data,
 * meaning parent methods will not be copied and attached to child classes.
 *
 * The index keeps track of 'outlines' that are confined to a single file. It in itself does not do anything
 * "intelligent" such as automatically inheriting docblocks from overridden methods.
 */
final class StorageFileIndexer implements FileIndexerInterface
{
    /**
     * The storage to use for index data.
     *
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
     * @var array
     */
    private $accessModifierMap;

    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @param StorageInterface                           $storage
     * @param TypeAnalyzer                               $typeAnalyzer
     * @param DocblockParser                             $docblockParser
     * @param NodeTypeDeducerInterface                   $nodeTypeDeducer
     * @param Parser                                     $parser
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     */
    public function __construct(
        StorageInterface $storage,
        TypeAnalyzer $typeAnalyzer,
        DocblockParser $docblockParser,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        Parser $parser,
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
    ) {
        $this->storage = $storage;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockParser = $docblockParser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->parser = $parser;
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
    }

    /**
     * @inheritDoc
     */
    public function index(string $filePath, string $code): void
    {
        $this->storage->beginTransaction();

        try {
            $file = $this->storage->getFileByPath($filePath);
            $file->setIndexedOn(new DateTime());
        } catch (FileNotFoundStorageException $e) {
            $file = new Structures\File($filePath, new DateTime(), []);
        }

        $this->storage->persist($file);

        try {
            $nodes = $this->getNodes($code);

            // NOTE: Traversing twice may seem absurd, but a rewrite of the use statement indexing visitor to support
            // on-the-fly indexing (i.e. not after the traversal, so it does not need to run separately) seemed to make
            // performance worse, because of the constant flushing and entity changes due to the end lines being
            // recalculated, than just traversing twice.
            $this->indexNamespacesWithUseStatements($file, $nodes, $code);
            $this->indexCode($file, $nodes, $code);

            $this->storage->commitTransaction();
        } catch (Error $e) {
            $this->storage->rollbackTransaction();

            throw new IndexingFailedException($e->getMessage(), 0, $e);
        } catch (Exception $e) {
            $this->storage->rollbackTransaction();

            throw new AssertionError(
                'Could not index file due to an internal exception. This likely means an exception should be caught ' .
                'at a deeper level (if it is acceptable) or there is a bug. The file is "' . $filePath . '" and the ' .
                'exact exception message: "' . $e->getMessage() . '"',
                0,
                $e
            );
        }
    }

    /**
     * @param string $code
     *
     * @throws Error
     *
     * @return array
     */
    private function getNodes(string $code): array
    {
        $handler = new ErrorHandler\Collecting();

        $nodes = $this->parser->parse($code, $handler);

        if ($nodes === null) {
            throw new Error('Unknown syntax error encountered');
        }

        return $nodes;
    }

    /**
     * @param Structures\File $file
     * @param string          $code
     * @param array           $nodes
     *
     * @throws Exception
     */
    private function indexNamespacesWithUseStatements(Structures\File $file, array $nodes, string $code): void
    {
        $useStatementIndexingVisitor = new Visiting\UseStatementIndexingVisitor($this->storage, $file, $code);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new Visiting\ClassLikeBodySkippingVisitor());
        $traverser->addVisitor(new Visiting\FunctionLikeBodySkippingVisitor());
        $traverser->addVisitor($useStatementIndexingVisitor);

        $this->storage->beginTransaction();

        try {
            $traverser->traverse($nodes);

            $this->storage->commitTransaction();
        } catch (Exception $e) {
            $this->storage->rollbackTransaction();

            throw $e;
        }
    }

    /**
     * @param Structures\File $file
     * @param array           $nodes
     * @param string          $code
     *
     * @throws Exception
     */
    private function indexCode(Structures\File $file, array $nodes, string $code): void
    {
        $traverser = new NodeTraverser();

        foreach ($this->getIndexingVisitors($code, $file) as $visitor) {
            $traverser->addVisitor($visitor);
        }

        $this->storage->beginTransaction();

        try {
            $traverser->traverse($nodes);

            $this->storage->commitTransaction();
        } catch (Exception $e) {
            $this->storage->rollbackTransaction();

            throw $e;
        }
    }

    /**
     * @param string          $code
     * @param Structures\File $file
     *
     * @return array
     */
    private function getIndexingVisitors(string $code, Structures\File $file): array
    {
        return [
            new Visiting\ConstantIndexingVisitor(
                $this->storage,
                $this->docblockParser,
                $this->structureAwareNameResolverFactory,
                $this->typeAnalyzer,
                $this->nodeTypeDeducer,
                $file,
                $code
            ),

            new Visiting\DefineIndexingVisitor(
                $this->storage,
                $this->nodeTypeDeducer,
                $file,
                $code
            ),

            new Visiting\FunctionIndexingVisitor(
                $this->structureAwareNameResolverFactory,
                $this->storage,
                $this->docblockParser,
                $this->typeAnalyzer,
                $this->nodeTypeDeducer,
                $file,
                $code
            ),

            new Visiting\ClasslikeIndexingVisitor(
                $this->storage,
                $this->typeAnalyzer,
                $this->docblockParser,
                $this->nodeTypeDeducer,
                $this->structureAwareNameResolverFactory,
                $file,
                $code
            ),

            new Visiting\MetaStaticMethodTypeIndexingVisitor(
                $this->storage,
                $file
            )
        ];
    }
}
