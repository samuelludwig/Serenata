<?php

namespace PhpIntegrator\Indexing\Visiting;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\TypeNormalizerInterface;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolver;
use PhpIntegrator\Analysis\Typing\Resolving\TypeResolverInterface;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

use PhpIntegrator\Indexing\StorageInterface;
use PhpIntegrator\Indexing\IndexStorageItemEnum;

use PhpIntegrator\Parsing\DocblockParser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that traverses a set of nodes, indexing global defines in the process.
 */
final class GlobalDefineIndexingVisitor extends NodeVisitorAbstract
{
    /**
     * @var FileTypeResolverFactoryInterface
     */
    private $fileTypeResolverFactory;

    /**
     * @var TypeNormalizerInterface
     */
    private $typeNormalizer;

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
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var int
     */
    private $fileId;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param TypeNormalizerInterface          $typeNormalizer
     * @param StorageInterface                 $storage
     * @param DocblockParser                   $docblockParser
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param TypeResolverInterface            $typeResolver
     * @param NodeTypeDeducerInterface         $nodeTypeDeducer
     * @param int                              $fileId
     * @param string                           $code
     * @param string                           $filePath
     */
    public function __construct(
        FileTypeResolverFactoryInterface $fileTypeResolverFactory,
        TypeNormalizerInterface $typeNormalizer,
        StorageInterface $storage,
        DocblockParser $docblockParser,
        TypeAnalyzer $typeAnalyzer,
        TypeResolverInterface $typeResolver,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        int $fileId,
        string $code,
        string $filePath
    ) {
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->typeNormalizer = $typeNormalizer;
        $this->storage = $storage;
        $this->docblockParser = $docblockParser;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->typeResolver = $typeResolver;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->fileId = $fileId;
        $this->code = $code;
        $this->filePath = $filePath;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Expr\FuncCall &&
            $node->name instanceof Node\Name &&
            $node->name->toString() === 'define'
        ) {
            $this->parseDefineNode($node);
        }
    }

    /**
     * @param Node\Expr\FuncCall $node
     *
     * @return void
     */
    protected function parseDefineNode(Node\Expr\FuncCall $node): void
    {
        if (count($node->args) < 2) {
            return;
        }

        $nameValue = $node->args[0]->value;

        if (!$nameValue instanceof Node\Scalar\String_) {
            return;
        }

        // Defines can be namespaced if their name contains slashes, see also
        // https://php.net/manual/en/function.define.php#90282
        $name = new Node\Name((string) $nameValue->value);

        $fileTypeResolver = $this->fileTypeResolverFactory->create($this->filePath);

        $fqcn = $this->typeNormalizer->getNormalizedFqcn(
            isset($node->namespacedName) ? $node->namespacedName->toString() : $name->getLast()
        );

        $docComment = $node->getDocComment() ? $node->getDocComment()->getText() : null;

        $documentation = $this->docblockParser->parse($docComment, [
            DocblockParser::VAR_TYPE,
            DocblockParser::DEPRECATED,
            DocblockParser::DESCRIPTION
        ], $name->getLast());

        $varDocumentation = isset($documentation['var']['$' . $name->getLast()]) ?
            $documentation['var']['$' . $name->getLast()] :
            null;

        $shortDescription = $documentation['descriptions']['short'];

        $types = [];

        $defaultValue = substr(
            $this->code,
            $node->args[1]->getAttribute('startFilePos'),
            $node->args[1]->getAttribute('endFilePos') - $node->args[1]->getAttribute('startFilePos') + 1
        );

        if ($node->args[1]) {
            $typeList = $this->nodeTypeDeducer->deduce(
                $node->args[1],
                $this->filePath,
                $defaultValue,
                0
            );

            $types = $this->getTypeDataForTypeList($typeList, $node->getLine(), $fileTypeResolver);
        }

        $this->storage->insert(IndexStorageItemEnum::CONSTANTS, [
            'name'                  => $name->getLast(),
            'fqcn'                  => $this->typeNormalizer->getNormalizedFqcn($name->toString()),
            'file_id'               => $this->fileId,
            'start_line'            => $node->getLine(),
            'end_line'              => $node->getAttribute('endLine'),
            'default_value'         => $defaultValue,
            'is_builtin'            => 0,
            'is_deprecated'         => $documentation['deprecated'] ? 1 : 0,
            'has_docblock'          => empty($docComment) ? 0 : 1,
            'short_description'     => $shortDescription,
            'long_description'      => $documentation['descriptions']['long'],
            'type_description'      => $varDocumentation ? $varDocumentation['description'] : null,
            'types_serialized'      => serialize($types),
            'structure_id'          => null,
            'access_modifier_id'    => null
        ]);
    }

    /**
     * @param string           $typeSpecification
     * @param int              $line
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return array[]
     */
    protected function getTypeDataForTypeSpecification(
        string $typeSpecification,
        int $line,
        FileTypeResolver $fileTypeResolver
    ): array {
        $typeList = $this->typeAnalyzer->getTypesForTypeSpecification($typeSpecification);

        return $this->getTypeDataForTypeList($typeList, $line, $fileTypeResolver);
    }

    /**
     * @param string[]         $typeList
     * @param int              $line
     * @param FileTypeResolver $fileTypeResolver
     *
     * @return array[]
     */
    protected function getTypeDataForTypeList(array $typeList, int $line, FileTypeResolver $fileTypeResolver): array
    {
        $types = [];

        foreach ($typeList as $type) {
            $types[] = [
                'type' => $type,
                'fqcn' => $fileTypeResolver->resolve($type, $line)
            ];
        }

        return $types;
    }
}