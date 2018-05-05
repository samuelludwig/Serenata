<?php

namespace Serenata\Tests\Integration\SignatureHelp;

use UnexpectedValueException;

use Serenata\SignatureHelp\SignatureHelp;
use Serenata\SignatureHelp\ParameterInformation;
use Serenata\SignatureHelp\SignatureInformation;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class SignatureHelpRetrieverTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testFunctionCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(int $a, bool $b = true, string $c)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $fileName = 'FunctionCall.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 179, 186, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 179, 180, 0);
        static::assertSignatureHelpActiveParameterEquals($fileName, 181, 183, 1);
        static::assertSignatureHelpActiveParameterEquals($fileName, 184, 186, 2);
    }

    /**
     * @return void
     */
    public function testFunctionCallWhitespaceBeforeFirstAndOnlyArgument(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(int $a)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.')
            ])
        ];

        $fileName = 'FunctionCallWhitespaceBeforeFirstAndOnlyArgument.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 108, 110, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 108, 110, 0);
    }

    /**
     * @return void
     */
    public function testFunctionCallWhitespaceBetweenArguments(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(int $a, int $b)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('int $b', 'Parameter B.')
            ])
        ];

        $fileName = 'FunctionCallWhitespaceBetweenArguments.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 142, 147, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 142, 144, 0);
        static::assertSignatureHelpActiveParameterEquals($fileName, 145, 147, 1);
    }

    /**
     * @return void
     */
    public function testFunctionCallWithMissingLastArgumentAfterComma(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(int $a, int $b)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('int $b', 'Parameter B.')
            ])
        ];

        $fileName = 'FunctionCallWithMissingLastArgumentAfterComma.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 142, 144, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 142, 143, 0);
        static::assertSignatureHelpActiveParameterEquals($fileName, 144, 144, 1);
    }

    // /**
    //  * @return void
    //  */
    // public function testFunctionCallDoesNotWorkWhenInsideClosureArgumentBody(): void
    // {
    //     $expectedSignaturesResult = [
    //         new SignatureInformation('test(\Closure $a)', null, [
    //             new ParameterInformation('\Closure $a', null)
    //         ])
    //     ];
    //
    //     $fileName = 'FunctionCallClosureArgumentBody.phpt';
    //
    //     static::assertSignatureHelpSignaturesEquals($fileName, 80, 94, $expectedSignaturesResult);
    //
    //     $hadException = false;
    //
    //     try {
    //         static::assertSignatureHelpSignaturesEquals($fileName, 95, 95, $expectedSignaturesResult);
    //     } catch (UnexpectedValueException $e) {
    //         $hadException = true;
    //     }
    //
    //     static::assertTrue($hadException, 'Signature help should not trigger inside the body of closure arguments');
    //
    //     $hadException = false;
    //
    //     try {
    //         static::assertSignatureHelpSignaturesEquals($fileName, 101, 101, $expectedSignaturesResult);
    //     } catch (UnexpectedValueException $e) {
    //         $hadException = true;
    //     }
    //
    //     static::assertTrue($hadException, 'Signature help should not trigger inside the body of closure arguments');
    //
    //     static::assertSignatureHelpSignaturesEquals($fileName, 102, 102, $expectedSignaturesResult);
    // }

    /**
     * @return void
     */
    public function testNestedFunctionCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('foo(int $a)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.')
            ])
        ];

        $expectedNestedSignaturesResult = [
            new SignatureInformation('bar()', null, [])
        ];

        $fileName = 'NestedFunctionCall.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 127, 131, $expectedSignaturesResult);
        static::assertSignatureHelpSignaturesEquals($fileName, 132, 134, $expectedNestedSignaturesResult);
        static::assertSignatureHelpSignaturesEquals($fileName, 135, 136, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 127, 131, 0);
        static::assertSignatureHelpActiveParameterEquals($fileName, 132, 134, null);
        static::assertSignatureHelpActiveParameterEquals($fileName, 135, 136, 0);
    }

    /**
     * @return void
     */
    public function testMethodCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(int $a, bool $b = true, string $c)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $fileName = 'MethodCall.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 245, 252, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 245, 246, 0);
        static::assertSignatureHelpActiveParameterEquals($fileName, 247, 249, 1);
        static::assertSignatureHelpActiveParameterEquals($fileName, 250, 252, 2);
    }

    /**
     * @return void
     */
    public function testNestedMethodCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('foo(int $a)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.')
            ])
        ];

        $expectedNestedSignaturesResult = [
            new SignatureInformation('bar()', null, [])
        ];

        $fileName = 'NestedMethodCall.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 221, 232, $expectedSignaturesResult);
        static::assertSignatureHelpSignaturesEquals($fileName, 233, 235, $expectedNestedSignaturesResult);
        static::assertSignatureHelpSignaturesEquals($fileName, 236, 237, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 221, 232, 0);
        static::assertSignatureHelpActiveParameterEquals($fileName, 233, 235, null);
        static::assertSignatureHelpActiveParameterEquals($fileName, 236, 237, 0);
    }

    /**
     * @return void
     */
    public function testStaticMethodCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(int $a, bool $b = true, string $c)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $fileName = 'StaticMethodCall.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 253, 260, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 253, 254, 0);
        static::assertSignatureHelpActiveParameterEquals($fileName, 255, 257, 1);
        static::assertSignatureHelpActiveParameterEquals($fileName, 258, 260, 2);
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('__construct(int $a, bool $b = true, string $c)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $fileName = 'Constructor.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 256, 309, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 256, 270, 0);
        static::assertSignatureHelpActiveParameterEquals($fileName, 271, 285, 1);
        static::assertSignatureHelpActiveParameterEquals($fileName, 286, 309, 2);
    }

    /**
     * @return void
     */
    public function testNestedConstructor(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('__construct(int $a)', null, [
                new ParameterInformation('int $a', null)
            ])
        ];

        $expectedNestedSignaturesResult = [
            new SignatureInformation('__construct(int $b)', null, [
                new ParameterInformation('int $b', null)
            ])
        ];

        $fileName = 'NestedConstructor.phpt';

        static::assertSignatureHelpSignaturesEquals($fileName, 219, 227, $expectedSignaturesResult);
        static::assertSignatureHelpSignaturesEquals($fileName, 228, 231, $expectedNestedSignaturesResult);
        static::assertSignatureHelpSignaturesEquals($fileName, 232, 233, $expectedSignaturesResult);
        static::assertSignatureHelpActiveParameterEquals($fileName, 219, 233, 0);
    }

    /**
     * @return void
     */
    public function testArgumentIndexIsNullForFunctionCallFunctionWithoutArguments(): void
    {
        $result = $this->getSignatureHelp('FunctionCallFunctionWithoutArguments.phpt', 48);

        static::assertCount(1, $result->getSignatures());
        static::assertEmpty($result->getSignatures()[0]->getParameters());
        static::assertNull($result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testArgumentIndexIsCorrectWithVariadicParameters(): void
    {
        $result = $this->getSignatureHelp('VariadicParameter.phpt', 217);

        static::assertCount(1, $result->getSignatures());
        static::assertCount(2, $result->getSignatures()[0]->getParameters());
        static::assertSame(1, $result->getActiveParameter());

        $result = $this->getSignatureHelp('VariadicParameter.phpt', 220);

        static::assertCount(1, $result->getSignatures());
        static::assertCount(2, $result->getSignatures()[0]->getParameters());
        static::assertSame(1, $result->getActiveParameter());
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
    public function testFunctionCallFailsWhenTrailingCommaIsPresentThatWouldBeFollowedByOutOfBoundsArgument(): void
    {
        $result = $this->getSignatureHelp('TrailingCommaIsPresentThatWouldBeFollowedByOutOfBoundsArgument.phpt', 97);
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testDynamicFunctionNameFails(): void
    {
        $result = $this->getSignatureHelp('FunctionCallDynamic.phpt', 49);
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testDynamicMethodNameFails(): void
    {
        $result = $this->getSignatureHelp('MethodCallDynamic.phpt', 85);
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testDynamicConstructorCallFails(): void
    {
        $result = $this->getSignatureHelp('ConstructorDynamic.phpt', 111);
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

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testNoInvocationWithMissingMemberNameFails(): void
    {
        $result = $this->getSignatureHelp('NoInvocationMissingMember.phpt', 17);
    }

    /**
     * @param string $file
     * @param int    $position
     *
     * @return SignatureHelp
     */
    private function getSignatureHelp(string $file, int $position): SignatureHelp
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $code = $this->container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        $file = $this->container->get('storage')->getFileByPath($path);

        return $this->container->get('signatureHelpRetriever')->get($file, $code, $position);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return __DIR__ . '/SignatureHelpTest/' . $file;
    }

    /**
     * @param string                 $fileName
     * @param int                    $start
     * @param int                    $end
     * @param SignatureInformation[] $signatures
     */
    private function assertSignatureHelpSignaturesEquals(
        string $fileName,
        int $start,
        int $end,
        array $signatures
    ): void {
        $i = $start;

        while ($i <= $end) {
            $result = $this->getSignatureHelp($fileName, $i);

            static::assertSame(0, $result->getActiveSignature());
            static::assertEquals($signatures, $result->getSignatures());

            ++$i;
        }

        // Assert that the range doesn't extend longer than it should.
        $gotException = false;

        try {
            $resultBeforeRange = $this->getSignatureHelp($fileName, $start - 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        static::assertTrue(
            $gotException === true || ($gotException === false && $resultBeforeRange->getSignatures() != $signatures),
            "Range does not start exactly at position {$start}, but seems to continue before it"
        );

        $gotException = false;

        try {
            $resultAfterRange = $this->getSignatureHelp($fileName, $end + 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        static::assertTrue(
            $gotException === true || ($gotException === false && $resultAfterRange->getSignatures() != $signatures),
            "Range does not end exactly at position {$end}, but seems to continue after it"
        );
    }

    /**
     * @param string   $fileName
     * @param int      $start
     * @param int      $end
     * @param int|null $activeParameter
     */
    private function assertSignatureHelpActiveParameterEquals(
        string $fileName,
        int $start,
        int $end,
        ?int $activeParameter
    ): void {
        $i = $start;

        while ($i <= $end) {
            $result = $this->getSignatureHelp($fileName, $i);

            static::assertSame($activeParameter, $result->getActiveParameter());

            ++$i;
        }

        // Assert that the range doesn't extend longer than it should.
        $gotException = false;

        try {
            $resultBeforeRange = $this->getSignatureHelp($fileName, $start - 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        static::assertTrue(
            $gotException === true || ($gotException === false && $resultBeforeRange->getActiveParameter() !== $activeParameter),
            "Range does not start exactly at position {$start}, but seems to continue before it"
        );

        $gotException = false;

        try {
            $resultAfterRange = $this->getSignatureHelp($fileName, $end + 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        static::assertTrue(
            $gotException === true || ($gotException === false && $resultAfterRange->getActiveParameter() !== $activeParameter),
            "Range does not end exactly at position {$end}, but seems to continue after it"
        );
    }
}
