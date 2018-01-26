<?php

namespace PhpIntegrator\Tests\Unit\Parsing;

use PhpIntegrator\DocblockTypeParser\IntDocblockType;
use PhpIntegrator\DocblockTypeParser\NullDocblockType;
use PhpIntegrator\DocblockTypeParser\StringDocblockType;
use PhpIntegrator\DocblockTypeParser\CompoundDocblockType;

use PhpIntegrator\Parsing\DocblockParser;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class DocblockParserTest extends AbstractIntegrationTest
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
                'type'        => new StringDocblockType(),
                'description' => 'Test description.',
                'isVariadic'  => false,
                'isReference' => false
            ]
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
                'type'        => new StringDocblockType(),
                'description' => 'Test description with @ sign.',
                'isVariadic'  => false,
                'isReference' => false
            ]
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
                'type' => new CompoundDocblockType(
                    new StringDocblockType(),
                    new NullDocblockType()
                ),

                'description' => 'Имя файла пат',
                'isVariadic'  => false,
                'isReference' => false
            ]
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
                'type'        => new IntDocblockType(),
                'description' => ''
            ]
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
                'type'        => new IntDocblockType(),
                'description' => 'Some description'
            ]
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

        static::assertSame([
            [
                'type'        => '\UnexpectedValueException',
                'description' => 'Some description'
            ]
        ], $result['throws']);
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

        static::assertSame([
            [
                'type'        => '\UnexpectedValueException',
                'description' => null
            ]
        ], $result['throws']);
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
                'description' => ''
            ]
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
    public function testLeavesHtmlAsIs(): void
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
     * @return DocblockParser
     */
    private function getDocblockParser(): DocblockParser
    {
        return $this->container->get('docblockParser');
    }
}
