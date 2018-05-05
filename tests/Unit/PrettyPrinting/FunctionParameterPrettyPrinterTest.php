<?php

namespace Serenata\Tests\Unit\PrettyPrinting;

use Serenata\PrettyPrinting\TypePrettyPrinter;
use Serenata\PrettyPrinting\TypeListPrettyPrinter;
use Serenata\PrettyPrinting\ParameterNamePrettyPrinter;
use Serenata\PrettyPrinting\FunctionParameterPrettyPrinter;
use Serenata\PrettyPrinting\ParameterDefaultValuePrettyPrinter;

class FunctionParameterPrettyPrinterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return FunctionParameterPrettyPrinter
     */
    private function getFunctionParameterPrettyPrinterStub(): FunctionParameterPrettyPrinter
    {
        return new FunctionParameterPrettyPrinter(
            new ParameterDefaultValuePrettyPrinter(),
            new TypeListPrettyPrinter(
                new TypePrettyPrinter()
            ),
            new ParameterNamePrettyPrinter()
        );
    }

    /**
     * @return void
     */
    public function testName(): void
    {
        $result = $this->getFunctionParameterPrettyPrinterStub()->print([
            'name'         => 'test',
            'isVariadic'   => false,
            'isReference'  => false,
            'defaultValue' => null,
            'types'        => []
        ]);

        static::assertSame('$test', $result);
    }

    /**
     * @return void
     */
    public function testReference(): void
    {
        $result = $this->getFunctionParameterPrettyPrinterStub()->print([
            'name'         => 'test',
            'isVariadic'   => false,
            'isReference'  => true,
            'defaultValue' => null,
            'types'        => []
        ]);

        static::assertSame('&$test', $result);
    }

    /**
     * @return void
     */
    public function testVariadic(): void
    {
        $result = $this->getFunctionParameterPrettyPrinterStub()->print([
            'name'         => 'test',
            'isVariadic'   => true,
            'isReference'  => false,
            'defaultValue' => null,
            'types'        => []
        ]);

        static::assertSame('...$test', $result);
    }

    /**
     * @return void
     */
    public function testSingleType(): void
    {
        $result = $this->getFunctionParameterPrettyPrinterStub()->print([
            'name'         => 'test',
            'isVariadic'   => false,
            'isReference'  => false,
            'defaultValue' => null,

            'types' => [
                [
                    'type' => 'int'
                ]
            ]
        ]);

        static::assertSame('int $test', $result);
    }

    /**
     * @return void
     */
    public function testMultipleTypes(): void
    {
        $result = $this->getFunctionParameterPrettyPrinterStub()->print([
            'name'         => 'test',
            'isVariadic'   => false,
            'isReference'  => false,
            'defaultValue' => null,

            'types' => [
                [
                    'type' => 'int'
                ],

                [
                    'type' => 'bool'
                ]
            ]
        ]);

        static::assertSame('int|bool $test', $result);
    }

    /**
     * @return void
     */
    public function testDefaultValue(): void
    {
        $result = $this->getFunctionParameterPrettyPrinterStub()->print([
            'name'         => 'test',
            'isVariadic'   => false,
            'isReference'  => false,
            'defaultValue' => 'null',
            'types'        => []
        ]);

        static::assertSame('$test = null', $result);
    }

    /**
     * @return void
     */
    public function testDefaultValueOfIntegerZero(): void
    {
        $result = $this->getFunctionParameterPrettyPrinterStub()->print([
            'name'         => 'test',
            'isVariadic'   => false,
            'isReference'  => false,
            'defaultValue' => 0,
            'types'        => []
        ]);

        static::assertSame('$test = 0', $result);
    }
}
