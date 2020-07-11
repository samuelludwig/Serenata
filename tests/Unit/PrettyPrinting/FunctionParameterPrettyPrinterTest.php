<?php

namespace Serenata\Tests\Unit\PrettyPrinting;

use PHPUnit\Framework\TestCase;

use Serenata\PrettyPrinting\TypePrettyPrinter;
use Serenata\PrettyPrinting\TypeListPrettyPrinter;
use Serenata\PrettyPrinting\ParameterNamePrettyPrinter;
use Serenata\PrettyPrinting\FunctionParameterPrettyPrinter;
use Serenata\PrettyPrinting\ParameterDefaultValuePrettyPrinter;

final class FunctionParameterPrettyPrinterTest extends TestCase
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
            'types'        => [],
        ]);

        self::assertSame('$test', $result);
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
            'types'        => [],
        ]);

        self::assertSame('&$test', $result);
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
            'types'        => [],
        ]);

        self::assertSame('...$test', $result);
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
                    'type' => 'int',
                ],
            ],
        ]);

        self::assertSame('int $test', $result);
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
                    'type' => 'int',
                ],

                [
                    'type' => 'bool',
                ],
            ],
        ]);

        self::assertSame('int|bool $test', $result);
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
            'types'        => [],
        ]);

        self::assertSame('$test = null', $result);
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
            'types'        => [],
        ]);

        self::assertSame('$test = 0', $result);
    }
}
