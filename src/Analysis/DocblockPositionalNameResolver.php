<?php

namespace Serenata\Analysis;

use Closure;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use Serenata\Analysis\Typing\TypeAnalyzer;

use Serenata\Common\FilePosition;

use Serenata\NameQualificationUtilities\NameKind;
use Serenata\NameQualificationUtilities\PositionalNameResolverInterface;

use Serenata\Parsing\DocblockTypeParserInterface;
use Serenata\Parsing\DocblockTypeTransformerInterface;

/**
 * Name resolver that can resolve docblock names to their FQCN.
 *
 * This class is also usable as a regular (non-docblock) type resolver as docblock names are a superset of standard
 * names. The additional functionality is decorated on top of the standard resolution process.
 */
final class DocblockPositionalNameResolver implements PositionalNameResolverInterface
{
    /**
     * @var PositionalNameResolverInterface
     */
    private $delegate;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * @var DocblockTypeTransformerInterface
     */
    private $docblockTypeTransformer;

    /**
     * @param PositionalNameResolverInterface  $delegate
     * @param TypeAnalyzer                     $typeAnalyzer
     * @param DocblockTypeParserInterface      $docblockTypeParser
     * @param DocblockTypeTransformerInterface $docblockTypeTransformer
     */
    public function __construct(
        PositionalNameResolverInterface $delegate,
        TypeAnalyzer $typeAnalyzer,
        DocblockTypeParserInterface $docblockTypeParser,
        DocblockTypeTransformerInterface $docblockTypeTransformer
    ) {
        $this->delegate = $delegate;
        $this->typeAnalyzer = $typeAnalyzer;
        $this->docblockTypeParser = $docblockTypeParser;
        $this->docblockTypeTransformer = $docblockTypeTransformer;
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $name, FilePosition $filePosition, string $kind = NameKind::CLASSLIKE): string
    {
        return (string) $this->docblockTypeTransformer->transform(
            $this->docblockTypeParser->parse($name),
            $this->createTransformer($filePosition, $kind)
        );
    }

    /**
     * @param FilePosition $filePosition
     *
     * @return Closure
     */
    private function createTransformer(FilePosition $filePosition, string $kind): Closure
    {
        return function (TypeNode $type) use ($filePosition, $kind): TypeNode {
            if ($type instanceof IdentifierTypeNode && $this->typeAnalyzer->isClassType((string) $type)) {
                return new IdentifierTypeNode($this->delegate->resolve((string) $type, $filePosition, $kind));
            }

            return $type;
        };
    }
}
