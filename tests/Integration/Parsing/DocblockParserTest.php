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

        self::assertEquals([
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

        self::assertEquals([
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

        self::assertEquals([
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

        self::assertEquals([
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

        self::assertEquals([
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

        self::assertSame([], $result['throws']);
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

        self::assertCount(1, $result['throws']);
        self::assertEquals(new IdentifierTypeNode('\UnexpectedValueException'), $result['throws'][0]['type']);
        self::assertSame('Some description', $result['throws'][0]['description']);
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

        self::assertCount(1, $result['throws']);
        self::assertEquals(new IdentifierTypeNode('\UnexpectedValueException'), $result['throws'][0]['type']);
        self::assertSame(null, $result['throws'][0]['description']);
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

        self::assertSame([], $result['var']);
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

        self::assertEquals([
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

        self::assertSame([], $result['params']);
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

        self::assertSame([], $result['params']);
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

        self::assertEquals([
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

        self::assertNull($result['return']);
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

        self::assertSame('This *is* _some_ markdown.', $result['descriptions']['short']);
        self::assertSame("```\nCode sample.\n```", $result['descriptions']['long']);
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

        self::assertSame('This **is** _some_ HTML.', $result['descriptions']['short']);
        self::assertSame("Code sample.", $result['descriptions']['long']);
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

        self::assertSame("```php\n    // Some indentation.\n```", $result['descriptions']['long']);
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

        self::assertSame('This alert("test")', $result['descriptions']['short']);
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
    //     self::assertSame('Multiple spaces with tabs.', $result['descriptions']['short']);
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

        self::assertSame("Description\n\nTest", $result['descriptions']['long']);
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

        self::assertSame("Description\n\nTest", $result['descriptions']['long']);
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

        self::assertSame('Summary.', $result['descriptions']['short']);
        self::assertSame('Description.', $result['descriptions']['long']);

        self::assertEquals(
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

        self::assertEquals([
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
