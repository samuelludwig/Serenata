<?php

namespace PhpIntegrator\Tests\Unit\SignatureHelp;

use UnexpectedValueException;

use PhpIntegrator\SignatureHelp\SignatureHelp;
use PhpIntegrator\SignatureHelp\ParameterInformation;
use PhpIntegrator\SignatureHelp\SignatureInformation;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class SignatureHelpRetrieverTest extends AbstractIntegrationTest
{
    /**
     * @param string $file
     * @param int    $position
     *
     * @return SignatureHelp
     */
    protected function getSignatureHelp(string $file, int $position): SignatureHelp
    {
        $path = $this->getPathFor($file);

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path);

        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        return $container->get('signatureHelpRetriever')->get($path, $code, $position);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/SignatureHelpTest/' . $file;
    }

    /**
     * @param string                 $fileName
     * @param int                    $start
     * @param int                    $end
     * @param SignatureInformation[] $signatures
     */
    protected function assertSignatureHelpSignaturesEquals(
        string $fileName,
        int $start,
        int $end,
        array $signatures
    ): void {
        $i = $start;

        while ($i <= $end) {
            $result = $this->getSignatureHelp($fileName, $i);

            $this->assertEquals(0, $result->getActiveSignature());
            $this->assertEquals($signatures, $result->getSignatures());

            ++$i;
        }

        // Assert that the range doesn't extend longer than it should.
        $gotException = false;

        try {
            $resultBeforeRange = $this->getSignatureHelp($fileName, $start - 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        $this->assertTrue(
            $gotException === true || ($gotException === false && $resultBeforeRange->getSignatures() !== $signatures),
            "Range does not start exactly at position {$start}, but seems to continue before it"
        );

        $gotException = false;

        try {
            $resultAfterRange = $this->getSignatureHelp($fileName, $end + 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        $this->assertTrue(
            $gotException === true || ($gotException === false && $resultAfterRange->getSignatures() !== $signatures),
            "Range does not end exactly at position {$end}, but seems to continue after it"
        );
    }

    /**
     * @param string $fileName
     * @param int    $start
     * @param int    $end
     * @param int    $activeParameter
     */
    protected function assertSignatureHelpActiveParameterEquals(
        string $fileName,
        int $start,
        int $end,
        int $activeParameter
    ): void {
        $i = $start;

        while ($i <= $end) {
            $result = $this->getSignatureHelp($fileName, $i);

            $this->assertEquals($activeParameter, $result->getActiveParameter());

            ++$i;
        }

        // Assert that the range doesn't extend longer than it should.
        $gotException = false;

        try {
            $resultBeforeRange = $this->getSignatureHelp($fileName, $start - 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        $this->assertTrue(
            $gotException === true || ($gotException === false && $resultBeforeRange->getActiveParameter() !== $activeParameter),
            "Range does not start exactly at position {$start}, but seems to continue before it"
        );

        $gotException = false;

        try {
            $resultAfterRange = $this->getSignatureHelp($fileName, $end + 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        $this->assertTrue(
            $gotException === true || ($gotException === false && $resultAfterRange->getActiveParameter() !== $activeParameter),
            "Range does not end exactly at position {$end}, but seems to continue after it"
        );
    }

    /**
     * @return void
     */
    public function testFunctionCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $fileName = 'FunctionCall.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 179, 186, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 179, 180, 0);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 181, 183, 1);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 184, 186, 2);
    }

    /**
     * @return void
     */
    public function testFunctionCallWhitespaceBeforeFirstAndOnlyArgument(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.')
            ])
        ];

        $fileName = 'FunctionCallWhitespaceBeforeFirstAndOnlyArgument.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 108, 110, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 108, 110, 0);
    }

    /**
     * @return void
     */
    public function testFunctionCallWhitespaceBetweenArguments(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('int $b', 'Parameter B.')
            ])
        ];

        $fileName = 'FunctionCallWhitespaceBetweenArguments.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 142, 147, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 142, 144, 0);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 145, 147, 1);
    }

    /**
     * @return void
     */
    public function testFunctionCallWithMissingLastArgumentAfterComma(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('int $b', 'Parameter B.')
            ])
        ];

        $fileName = 'FunctionCallWithMissingLastArgumentAfterComma.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 142, 144, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 142, 143, 0);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 144, 144, 1);
    }

    /**
     * @return void
     */
    public function testNestedFunctionCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('foo', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.')
            ])
        ];

        $expectedNestedSignaturesResult = [
            new SignatureInformation('bar', null, [])
        ];

        $fileName = 'NestedFunctionCall.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 127, 130, $expectedSignaturesResult);
        $this->assertSignatureHelpSignaturesEquals($fileName, 131, 131, $expectedNestedSignaturesResult);
        $this->assertSignatureHelpSignaturesEquals($fileName, 132, 132, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 127, 132, 0);
    }

    /**
     * @return void
     */
    public function testMethodCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $fileName = 'MethodCall.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 245, 252, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 245, 246, 0);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 247, 249, 1);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 250, 252, 2);
    }

    /**
     * @return void
     */
    public function testNestedMethodCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('foo', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.')
            ])
        ];

        $expectedNestedSignaturesResult = [
            new SignatureInformation('bar', null, [])
        ];

        $fileName = 'NestedMethodCall.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 221, 231, $expectedSignaturesResult);
        $this->assertSignatureHelpSignaturesEquals($fileName, 232, 232, $expectedNestedSignaturesResult);
        $this->assertSignatureHelpSignaturesEquals($fileName, 233, 233, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 221, 233, 0);
    }

    /**
     * @return void
     */
    public function testStaticMethodCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $fileName = 'StaticMethodCall.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 253, 260, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 253, 254, 0);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 255, 257, 1);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 258, 260, 2);
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('__construct', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $fileName = 'Constructor.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 256, 309, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 256, 270, 0);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 271, 285, 1);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 286, 309, 2);
    }

    /**
     * @return void
     */
    public function testNestedConstructor(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('__construct', null, [
                new ParameterInformation('int $a', null)
            ])
        ];

        $expectedNestedSignaturesResult = [
            new SignatureInformation('__construct', null, [
                new ParameterInformation('int $b', null)
            ])
        ];

        $fileName = 'NestedConstructor.phpt';

        $this->assertSignatureHelpSignaturesEquals($fileName, 219, 239, $expectedSignaturesResult);
        $this->assertSignatureHelpSignaturesEquals($fileName, 240, 241, $expectedNestedSignaturesResult);
        $this->assertSignatureHelpSignaturesEquals($fileName, 242, 251, $expectedSignaturesResult);
        $this->assertSignatureHelpActiveParameterEquals($fileName, 219, 251, 0);
    }

    /**
     * @return void
     */
    public function testArgumentIndexIsCorrectWithVariadicParameters(): void
    {
        $result = $this->getSignatureHelp('VariadicParameter.phpt', 217);

        $this->assertCount(1, $result->getSignatures());
        $this->assertCount(2, $result->getSignatures()[0]->getParameters());
        $this->assertEquals(1, $result->getActiveParameter());

        $result = $this->getSignatureHelp('VariadicParameter.phpt', 220);

        $this->assertCount(1, $result->getSignatures());
        $this->assertCount(2, $result->getSignatures()[0]->getParameters());
        $this->assertEquals(1, $result->getActiveParameter());
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testFunctionCallFunctionNameDoesNotWorkWhenFunctionDoesNotHaveArguments(): void
    {
        $result = $this->getSignatureHelp('FunctionCallFunctionNameWithoutArguments.phpt', 47);
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testFunctionCallFunctionNameDoesNotWorkWhenFunctionHasArguments(): void
    {
        $result = $this->getSignatureHelp('FunctionCallFunctionNameWithArguments.phpt', 53);
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testFunctionCallFailsWhenArgumentIsOutOfBounds(): void
    {
        $result = $this->getSignatureHelp('FunctionCallTooManyArguments.phpt', 113);
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testNoInvocationFails(): void
    {
        $result = $this->getSignatureHelp('NoInvocation.phpt', 233);
    }
}
