<?php

namespace Serenata\Tests\Integration\Parsing;

use PHPStan\PhpDocParser\Ast\Type;

use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use Serenata\Parsing\DocblockParser;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class DocblockParserTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testParamTagAtEndIsInterpretedCorrectly(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @param string $foo Test description.
             */
        ', [DocblockParser::PARAM_TYPE], '');

        static::assertEquals([
            '$foo' => [
                'type'        => new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::STRING_),
                'description' => 'Test description.',
                'isVariadic'  => false,
                'isReference' => false,
            ],
        ], $result['params']);
    }

    /**
     * @return void
     */
    public function testParamTagWithAtSymbolIsInterpretedCorrectly(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @param string $foo Test description with @ sign.
             */
        ', [DocblockParser::PARAM_TYPE], '');

        static::assertEquals([
            '$foo' => [
                'type'        => new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::STRING_),
                'description' => 'Test description with @ sign.',
                'isVariadic'  => false,
                'isReference' => false,
            ],
        ], $result['params']);
    }

    /**
     * @return void
     */
    public function testCorrectlyProcessesRussianUnicodeSequences(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('/**
     * @param string|null $someString Имя файла пат
     */', [DocblockParser::PARAM_TYPE], '');

        static::assertEquals([
            '$someString' => [
                'type' => new UnionTypeNode([
                    new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::STRING_),
                    new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::NULL_),
                ]),

                'description' => 'Имя файла пат',
                'isVariadic'  => false,
                'isReference' => false,
            ],
        ], $result['params']);
    }

    /**
     * @return void
     */
    public function testVarTagDescriptionStopsAtNextTag(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @var int
             *
             * @ORM\Column(type="integer")
             */
        ', [DocblockParser::VAR_TYPE], 'someProperty');

        static::assertEquals([
            '$someProperty' => [
                'type'        => new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::INT_),
                'description' => '',
            ],
        ], $result['var']);
    }

    /**
     * @return void
     */
    public function testVarTagInSingleLineCommentIsCorrectlyIdentified(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /** @var int Some description */
        ', [DocblockParser::VAR_TYPE], 'someProperty');

        static::assertEquals([
            '$someProperty' => [
                'type'        => new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::INT_),
                'description' => 'Some description',
            ],
        ], $result['var']);
    }

    /**
     * @return void
     */
    public function testThrowsTagWithoutType(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @throws
             */
        ', [DocblockParser::THROWS], '');

        static::assertSame([], $result['throws']);
    }

    /**
     * @return void
     */
    public function testThrowsTagWithDescription(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @throws \UnexpectedValueException Some description
             */
        ', [DocblockParser::THROWS], '');

        static::assertCount(1, $result['throws']);
        static::assertEquals(new IdentifierTypeNode('\UnexpectedValueException'), $result['throws'][0]['type']);
        static::assertSame('Some description', $result['throws'][0]['description']);
    }

    /**
     * @return void
     */
    public function testThrowsTagWithoutDescription(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @throws \UnexpectedValueException
             */
        ', [DocblockParser::THROWS], '');

        static::assertCount(1, $result['throws']);
        static::assertEquals(new IdentifierTypeNode('\UnexpectedValueException'), $result['throws'][0]['type']);
        static::assertSame(null, $result['throws'][0]['description']);
    }

    /**
     * @return void
     */
    public function testVarTagWithoutType(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @var
             */
        ', [DocblockParser::VAR_TYPE], '');

        static::assertSame([], $result['var']);
    }

    /**
     * @return void
     */
    public function testVarTagWithClassType(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @var \DateTime
             */
        ', [DocblockParser::VAR_TYPE], '');

        static::assertEquals([
            '$' => [
                'type'        => '\DateTime',
                'description' => '',
            ],
        ], $result['var']);
    }

    /**
     * @return void
     */
    public function testParamTagWithoutType(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @param
             */
        ', [DocblockParser::PARAM_TYPE], '');

        static::assertSame([], $result['params']);
    }

    /**
     * @return void
     */
    public function testParamTagWithoutName(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @param Type
             */
        ', [DocblockParser::PARAM_TYPE], '');

        static::assertSame([], $result['params']);
    }

    /**
     * @return void
     */
    public function testParamTagWithMultipleSpaces(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @param   string     $test    A description.
             */
        ', [DocblockParser::PARAM_TYPE], '');

        static::assertEquals([
            '$test' => [
                'type'        => new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::STRING_),
                'description' => 'A description.',
                'isVariadic'  => false,
                'isReference' => false,
            ],
        ], $result['params']);
    }

    /**
     * @return void
     */
    public function testReturnTagWithoutType(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * @return
             */
        ', [DocblockParser::RETURN_VALUE], '');

        static::assertNull($result['return']);
    }

    /**
     * @return void
     */
    public function testLeavesMarkdownAsIs(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * This *is* _some_ markdown.
             *
             * ```
             * Code sample.
             * ```
             */
        ', [DocblockParser::DESCRIPTION], '');

        static::assertSame('This *is* _some_ markdown.', $result['descriptions']['short']);
        static::assertSame("```\nCode sample.\n```", $result['descriptions']['long']);
    }

    /**
     * @return void
     */
    public function testConvertsHtmlToMarkdown(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * This <strong>is</strong> <i>some</i> HTML.
             *
             * <p>
             * Code sample.
             * </p>
             */
        ', [DocblockParser::DESCRIPTION], '');

        static::assertSame('This **is** _some_ HTML.', $result['descriptions']['short']);
        static::assertSame("Code sample.", $result['descriptions']['long']);
    }

    /**
     * @return void
     */
    public function testLeavesSpacesInMarkdownCodeBlocksIntact(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * Long description.
             *
             * ```php
             *     // Some indentation.
             * ```
             */
        ', [DocblockParser::DESCRIPTION], '');

        static::assertSame("```php\n    // Some indentation.\n```", $result['descriptions']['long']);
    }

    /**
     * @return void
     */
    public function testStripsInconvertableHtmlTags(): void
    {
        $parser = $this->getDocblockParser();
        $result = $parser->parse('
            /**
             * This <script>alert("test")</script>
             */
        ', [DocblockParser::DESCRIPTION], '');

        static::assertSame('This alert("test")', $result['descriptions']['short']);
    }

    // /**
    //  * @return void
    //  */
    // public function testCollapsesMultipleSpaces(): void
    // {
    //     $result = $this->getDocblockParser()->parse(
    //         "/**\n\t * Multiple  spaces\t with\t\ttabs.\n\t*/",
    //         [DocblockParser::DESCRIPTION],
    //         ''
    //     );
    //
    //     static::assertSame('Multiple spaces with tabs.', $result['descriptions']['short']);
    // }

    /**
     * @return void
     */
    public function testConvertsWindowsLineEndings(): void
    {
        $result = $this->getDocblockParser()->parse(
            "/**\r\n * Summary\r\n\r\nDescription\r\n\r\nTest\r\n*/",
            [DocblockParser::DESCRIPTION],
            ''
        );

        static::assertSame("Description\n\nTest", $result['descriptions']['long']);
    }

    /**
     * @return void
     */
    public function testConvertsMacosLineEndings(): void
    {
        $result = $this->getDocblockParser()->parse(
            "/**\r * Summary\r\rDescription\r\rTest\r*/",
            [DocblockParser::DESCRIPTION],
            ''
        );

        static::assertSame("Description\n\nTest", $result['descriptions']['long']);
    }

    /**
     * @return void
     */
    public function testProperlyDealsWithTabs(): void
    {
        $parser = $this->getDocblockParser();

        $result = $parser->parse(
            "/**\n\t * Summary.\n\t *\n\t * Description.\n\t *\n\t * @param string \$test\n\t *\n\t * @return bool\n\t*/",
            [
                DocblockParser::DESCRIPTION,
                DocblockParser::PARAM_TYPE,
                DocblockParser::RETURN_VALUE,
            ],
            ''
        );

        static::assertSame('Summary.', $result['descriptions']['short']);
        static::assertSame('Description.', $result['descriptions']['long']);

        static::assertEquals(
            [
                '$test' => [
                    'type'        => new Type\IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::STRING_),
                    'description' => null,
                    'isVariadic'  => false,
                    'isReference' => false,
                ],
            ],
            $result['params']
        );

        static::assertEquals([
            'type'        => new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::BOOL_),
            'description' => null,
        ], $result['return']);
    }

    /**
     * @return DocblockParser
     */
    private function getDocblockParser(): DocblockParser
    {
        return $this->container->get('docblockParser');
    }
}
