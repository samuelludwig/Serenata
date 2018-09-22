<?php

namespace Serenata\Indexing;

use DateTime;
use Exception;
use LogicException;

use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;

use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Analysis\Typing\TypeAnalyzer;
use Serenata\Analysis\Typing\TypeResolvingDocblockTypeTransformer;

use Serenata\DocblockTypeParser\DocblockTypeParserInterface;

use Serenata\Parsing\DocblockParser;

use Serenata\Utility\TextDocumentItem;

/**
 * Handles indexing PHP code in a single file.
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
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * @var TypeResolvingDocblockTypeTransformer
     */
    private $typeResolvingDocblockTypeTransformer;

    /**
     * @param StorageInterface                     $storage
     * @param TypeAnalyzer                         $typeAnalyzer
     * @param DocblockParser                       $docblockParser
     * @param NodeTypeDeducerInterface             $nodeTypeDeducer
     * @param Parser                               $parser
     * @param DocblockTypeParserInterface          $docblockTypeParser
     * @param TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer
     */
    public function __construct(
        StorageInterface $storage,
        TypeAnalyzer $typeAnalyzer,
        DocblockParser $docblockParser,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        Parser $parser,
        DocblockTypeParserInterface $docblockTypeParser,
        TypeResolvingDocblockTypeTransformer $typeResolvingDocblockTypeTransformer
    ) {
        $this->storage = $storage;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockParser = $docblockParser;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->parser = $parser;
        $this->docblockTypeParser = $docblockTypeParser;
        $this->typeResolvingDocblockTypeTransformer = $typeResolvingDocblockTypeTransformer;
    }

    /**
     * @inheritDoc
     */
    public function index(TextDocumentItem $textDocumentItem): void
    {
        $this->storage->beginTransaction();

        try {
            $file = $this->storage->getFileByUri($textDocumentItem->getUri());
            $file->setIndexedOn(new DateTime());
        } catch (FileNotFoundStorageException $e) {
            $file = new Structures\File($textDocumentItem->getUri(), new DateTime(), []);
        }

        $this->storage->persist($file);

        echo $textDocumentItem->getText() . PHP_EOL;

        try {
            $nodes = $this->getNodes($textDocumentItem->getText());

            // NOTE: Traversing twice may seem absurd, but a rewrite of the use statement indexing visitor to support
            // on-the-fly indexing (i.e. not after the traversal, so it does not need to run separately) seemed to make
            // performance worse, because of the constant flushing and entity changes due to the end lines being
            // recalculated, than just traversing twice.
            $this->indexNamespacesWithUseStatements($nodes, $file, $textDocumentItem);
            $this->indexCode($nodes, $file, $textDocumentItem);

            $this->storage->commitTransaction();
        } catch (Error $e) {
            $this->storage->rollbackTransaction();

            throw new IndexingFailedException($e->getMessage(), 0, $e);
        } catch (Exception $e) {
            $this->storage->rollbackTransaction();

            throw new LogicException(
                'Could not index file due to an internal exception. This likely means an exception should be caught ' .
                'at a deeper level (if it is acceptable) or there is a bug. The file is "' .
                $textDocumentItem->getUri() . '" and the exact exception message: "' . $e->getMessage() . '"',
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
     * @param array            $nodes
     * @param Structures\File  $file
     * @param TextDocumentItem $textDocumentItem
     *
     * @throws Exception
     */
    private function indexNamespacesWithUseStatements(
        array $nodes,
        Structures\File $file,
        TextDocumentItem $textDocumentItem
    ): void {
        $useStatementIndexingVisitor = new Visiting\UseStatementIndexingVisitor(
            $this->storage,
            $file,
            $textDocumentItem->getText()
        );

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
     * @param array            $nodes
     * @param Structures\File  $file
     * @param TextDocumentItem $textDocumentItem
     *
     * @throws Exception
     */
    private function indexCode(array $nodes, Structures\File $file, TextDocumentItem $textDocumentItem): void
    {
        $traverser = new NodeTraverser();

        foreach ($this->getIndexingVisitors($file, $textDocumentItem) as $visitor) {
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
     * @param Structures\File  $file
     * @param TextDocumentItem $textDocumentItem
     *
     * @return array
     */
    private function getIndexingVisitors(Structures\File $file, TextDocumentItem $textDocumentItem): array
    {
        return [
            new Visiting\ConstantIndexingVisitor(
                $this->storage,
                $this->docblockParser,
                $this->docblockTypeParser,
                $this->typeResolvingDocblockTypeTransformer,
                $this->nodeTypeDeducer,
                $file,
                $textDocumentItem
            ),

            new Visiting\DefineIndexingVisitor(
                $this->storage,
                $this->nodeTypeDeducer,
                $this->docblockTypeParser,
                $this->typeResolvingDocblockTypeTransformer,
                $file,
                $textDocumentItem
            ),

            new Visiting\FunctionIndexingVisitor(
                $this->storage,
                $this->docblockParser,
                $this->docblockTypeParser,
                $this->typeResolvingDocblockTypeTransformer,
                $this->nodeTypeDeducer,
                $file,
                $textDocumentItem
            ),

            new Visiting\ClasslikeIndexingVisitor(
                $this->storage,
                $this->typeAnalyzer,
                $this->docblockParser,
                $this->docblockTypeParser,
                $this->typeResolvingDocblockTypeTransformer,
                $this->nodeTypeDeducer,
                $file,
                $textDocumentItem
            ),

            new Visiting\MetaStaticMethodTypeIndexingVisitor(
                $this->storage,
                $file
            ),
        ];
    }
}
