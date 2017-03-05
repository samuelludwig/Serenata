<?php

namespace PhpIntegrator\Tests\Unit\Parsing;

use PhpIntegrator\Parsing\DocblockParser;

class DocblockParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testParamTagAtEndIsInterpretedCorrectly(): void
    {
        $parser = new DocblockParser();
        $result = $parser->parse('
            /**
             * @param string $foo Test description.
             */
        ', [DocblockParser::PARAM_TYPE], '');

        $this->assertEquals([
            '$foo' => [
                'type'        => 'string',
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
        $parser = new DocblockParser();
        $result = $parser->parse('
            /**
             * @param string $foo Test description with @ sign.
             */
        ', [DocblockParser::PARAM_TYPE], '');

        $this->assertEquals([
            '$foo' => [
                'type'        => 'string',
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
        $parser = new DocblockParser();
        $result = $parser->parse('/**
     * @param string|null $someString Имя файла пат
     */', [DocblockParser::PARAM_TYPE], '');

        $this->assertEquals([
            '$someString' => [
                'type'        => 'string|null',
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
        $parser = new DocblockParser();
        $result = $parser->parse('
            /**
             * @var int
             *
             * @ORM\Column(type="integer")
             */
        ', [DocblockParser::VAR_TYPE], 'someProperty');

        $this->assertEquals([
            '$someProperty' => [
                'type'        => 'int',
                'description' => ''
            ]
        ], $result['var']);
    }

    /**
     * @return void
     */
    public function testVarTagInSingleLineCommentIsCorrectlyIdentified(): void
    {
        $parser = new DocblockParser();
        $result = $parser->parse('
            /** @var int Some description */
        ', [DocblockParser::VAR_TYPE], 'someProperty');

        $this->assertEquals([
            '$someProperty' => [
                'type'        => 'int',
                'description' => 'Some description'
            ]
        ], $result['var']);
    }

    /**
     * @return void
     */
    public function testThrowsTagWithDescription(): void
    {
        $parser = new DocblockParser();
        $result = $parser->parse('
            /**
             * @throws \UnexpectedValueException Some description
             */
        ', [DocblockParser::THROWS], '');

        $this->assertEquals([
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
        $parser = new DocblockParser();
        $result = $parser->parse('
            /**
             * @throws \UnexpectedValueException
             */
        ', [DocblockParser::THROWS], '');

        $this->assertEquals([
            [
                'type'        => '\UnexpectedValueException',
                'description' => null
            ]
        ], $result['throws']);
    }
}
