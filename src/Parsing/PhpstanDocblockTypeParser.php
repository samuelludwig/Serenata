<?php

namespace Serenata\Parsing;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use PHPStan\PhpDocParser\Lexer\Lexer;

use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\ParserException;

/**
 * Parser for PHP docblocks.
 */
final class PhpstanDocblockTypeParser implements DocblockTypeParserInterface
{
    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * @var TypeParser
     */
    private $typeParser;

    /**
     * @param Lexer      $lexer
     * @param TypeParser $typeParser
     */
    public function __construct(Lexer $lexer, TypeParser $typeParser)
    {
        $this->lexer = $lexer;
        $this->typeParser = $typeParser;
    }

    /**
     * @param string $specification
     *
     * @return TypeNode
     */
    public function parse(string $specification): TypeNode
    {
        $tokens = new TokenIterator($this->lexer->tokenize($specification));

        try {
            return $this->typeParser->parse($tokens);
        } catch (ParserException $th) {
            return new InvalidTypeNode();
        }
    }
}
