<?php

namespace PhpIntegrator\Tests\Unit\Parsing;

use PhpIntegrator\Analysis\DocblockAnalyzer;

use PhpIntegrator\Parsing\DocblockParser;

use PhpIntegrator\Parsing\DocblockTypes\DocblockTypeParser;

class DocblockParserTest extends \PHPUnit\Framework\TestCase
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
        $parser = $this->getDocblockParser();
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
        $parser = $this->getDocblockParser();
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
        $parser = $this->getDocblockParser();
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
        $parser = $this->getDocblockParser();
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
        $parser = $this->getDocblockParser();
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
        $parser = $this->getDocblockParser();
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

    /**
     * @return DocblockParser
     */
    protected function getDocblockParser(): DocblockParser
    {
        return new DocblockParser(
            $this->getDocblockAnalyzer(),
            $this->getDocblockTypeParser()
        );
    }

    /**
     * @return DocblockAnalyzer
     */
    protected function getDocblockAnalyzer(): DocblockAnalyzer
    {
        return new DocblockAnalyzer();
    }

    /**
     * @return DocblockTypeParser
     */
    protected function getDocblockTypeParser(): DocblockTypeParser
    {
        return new DocblockTypeParser();
    }
}
