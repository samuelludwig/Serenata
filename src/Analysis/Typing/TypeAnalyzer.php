<?php

namespace Serenata\Analysis\Typing;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use Serenata\Parsing\DocblockTypeParserInterface;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

/**
 * Provides functionality for analyzing type names.
 *
 * @deprecated Determining if something is a special type or not can be done by using instanceof on AST nodes.
 */
final class TypeAnalyzer implements TypeNormalizerInterface
{
    /**
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * Constructor.
     *
     * @param DocblockTypeParserInterface $docblockTypeParser
     */
    public function __construct(DocblockTypeParserInterface $docblockTypeParser)
    {
        $this->docblockTypeParser = $docblockTypeParser;
    }

    /**
     * Indicates if a type is "special", i.e. it is not an actual class type, but rather a basic type (e.g. "int",
     * "bool", ...) or another special type (e.g. "$this", "false", ...).
     *
     * @param string $type
     *
     * @see https://github.com/phpDocumentor/fig-standards/blob/master/proposed/phpdoc.md#keyword
     *
     * @return bool
     *
     * @deprecated Use a DocblockTypeParserInterface and combine it with a check in SpecialDocblockTypeIdentifierLiteral
     *             instead. This will do the same, possibly resulting in unnecessary computations.
     */
    public function isSpecialType(string $type): bool
    {
        return !$this->isClassType($type);
    }

    /**
     * @param string $type
     *
     * @return bool
     *
     * @deprecated Use a DocblockTypeParserInterface and combine it with a check in SpecialDocblockTypeIdentifierLiteral
     *             instead. This will do the same, possibly resulting in unnecessary computations.
     */
    public function isClassType(string $type): bool
    {
        $type = $this->docblockTypeParser->parse($type);

        return $type instanceof IdentifierTypeNode &&
            !in_array($type->name, SpecialDocblockTypeIdentifierLiteral::getValues(), true);
    }

    /**
     * @inheritDoc
     */
    public function getNormalizedFqcn(string $fqcn): string
    {
        if ($fqcn !== '' && $fqcn[0] !== '\\') {
            return '\\' . $fqcn;
        }

        return $fqcn;
    }
}
