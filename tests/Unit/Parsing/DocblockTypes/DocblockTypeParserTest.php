<?php

namespace PhpIntegrator\Tests\Unit\Parsing\DocblockTypes;

use PhpIntegrator\Parsing\DocblockTypes;

use PhpIntegrator\Parsing\DocblockTypes\DocblockTypeParser;

class DocblockTypeParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testString(): void
    {
        $this->assertEquals(
            new DocblockTypes\StringDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\StringDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testInt(): void
    {
        $this->assertEquals(
            new DocblockTypes\IntDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\IntDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testInteger(): void
    {
        $this->assertEquals(
            new DocblockTypes\IntDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\IntDocblockType::STRING_VALUE_ALIAS)
        );
    }

    /**
     * @return void
     */
    public function testBool(): void
    {
        $this->assertEquals(
            new DocblockTypes\BoolDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\BoolDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testBoolean(): void
    {
        $this->assertEquals(
            new DocblockTypes\BoolDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\BoolDocblockType::STRING_VALUE_ALIAS)
        );
    }

    /**
     * @return void
     */
    public function testFloat(): void
    {
        $this->assertEquals(
            new DocblockTypes\FloatDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\FloatDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testDouble(): void
    {
        $this->assertEquals(
            new DocblockTypes\FloatDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\FloatDocblockType::STRING_VALUE_ALIAS)
        );
    }

    /**
     * @return void
     */
    public function testObject(): void
    {
        $this->assertEquals(
            new DocblockTypes\ObjectDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\ObjectDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testMixed(): void
    {
        $this->assertEquals(
            new DocblockTypes\MixedDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\MixedDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testArray(): void
    {
        $this->assertEquals(
            new DocblockTypes\ArrayDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\ArrayDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testResource(): void
    {
        $this->assertEquals(
            new DocblockTypes\ResourceDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\ResourceDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testVoid(): void
    {
        $this->assertEquals(
            new DocblockTypes\VoidDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\VoidDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testNull(): void
    {
        $this->assertEquals(
            new DocblockTypes\NullDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\NullDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testCallable(): void
    {
        $this->assertEquals(
            new DocblockTypes\CallableDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\CallableDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testFalse(): void
    {
        $this->assertEquals(
            new DocblockTypes\FalseDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\FalseDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testTrue(): void
    {
        $this->assertEquals(
            new DocblockTypes\TrueDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\TrueDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testSelf(): void
    {
        $this->assertEquals(
            new DocblockTypes\SelfDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\SelfDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testStatic(): void
    {
        $this->assertEquals(
            new DocblockTypes\StaticDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\StaticDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testThis(): void
    {
        $this->assertEquals(
            new DocblockTypes\ThisDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\ThisDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testIterable(): void
    {
        $this->assertEquals(
            new DocblockTypes\IterableDocblockType(),
            $this->getDocblockTypeParser()->parse(DocblockTypes\IterableDocblockType::STRING_VALUE)
        );
    }

    /**
     * @return void
     */
    public function testClassType(): void
    {
        $this->assertEquals(
            new DocblockTypes\ClassDocblockType('\A\B\C'),
            $this->getDocblockTypeParser()->parse('\A\B\C')
        );
    }

    /**
     * @return void
     */
    public function testSpecializedArray(): void
    {
        $this->assertEquals(
            new DocblockTypes\SpecializedArrayDocblockType(
                new DocblockTypes\IntDocblockType()
            ),
            $this->getDocblockTypeParser()->parse('int[]')
        );
    }

    /**
     * @return void
     */
    public function testCompoundTypes(): void
    {
        $this->assertEquals(
            new DocblockTypes\CompoundDocblockType(
                new DocblockTypes\IntDocblockType(),
                new DocblockTypes\ClassDocblockType('\A'),
                new DocblockTypes\ClassDocblockType('B'),
                new DocblockTypes\NullDocblockType()
            ),
            $this->getDocblockTypeParser()->parse('int|\A|B|null')
        );
    }

    /**
     * @return void
     */
    public function testOuterParanthesesAreIgnored(): void
    {
        $this->assertEquals(
            new DocblockTypes\CompoundDocblockType(
                new DocblockTypes\IntDocblockType(),
                new DocblockTypes\NullDocblockType()
            ),
            $this->getDocblockTypeParser()->parse('(int|null)')
        );
    }

    /**
     * @return void
     */
    public function testParanthesizedSpecializedArrays(): void
    {
        $this->assertEquals(
            new DocblockTypes\SpecializedArrayDocblockType(
                new DocblockTypes\CompoundDocblockType(
                    new DocblockTypes\IntDocblockType(),
                    new DocblockTypes\BoolDocblockType()
                )
            ),
            $this->getDocblockTypeParser()->parse('(int|bool)[]')
        );
    }

    /**
     * @return void
     */
    public function testTrailingCompoundTypeSeparatorIsAutomaticallyRemoved(): void
    {
        $this->assertEquals(
            new DocblockTypes\StringDocblockType(),
            $this->getDocblockTypeParser()->parse('string|')
        );
    }

    /**
     * @return DocblockTypeParser
     */
    protected function getDocblockTypeParser(): DocblockTypeParser
    {
        return new DocblockTypeParser();
    }
}
