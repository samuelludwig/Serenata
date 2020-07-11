<?php

namespace Serenata\Tests\Integration\SignatureHelp;

use Serenata\Common\Position;

use Serenata\SignatureHelp\SignatureHelp;
use Serenata\SignatureHelp\ParameterInformation;
use Serenata\SignatureHelp\SignatureInformation;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

final class SignatureHelpRetrieverTest extends AbstractIntegrationTest
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
                new ParameterInformation('string $c', 'Parameter C.'),
            ]),
        ];

        $fileName = 'FunctionCall.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 179, 186, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 179, 180, 0);
        self::assertSignatureHelpActiveParameterEquals($fileName, 181, 183, 1);
        self::assertSignatureHelpActiveParameterEquals($fileName, 184, 186, 2);
    }

    /**
     * @return void
     */
    public function testFunctionCallWhitespaceBeforeFirstAndOnlyArgument(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(int $a)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
            ]),
        ];

        $fileName = 'FunctionCallWhitespaceBeforeFirstAndOnlyArgument.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 108, 110, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 108, 110, 0);
    }

    /**
     * @return void
     */
    public function testFunctionCallWhitespaceBetweenArguments(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(int $a, int $b)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('int $b', 'Parameter B.'),
            ]),
        ];

        $fileName = 'FunctionCallWhitespaceBetweenArguments.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 142, 147, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 142, 144, 0);
        self::assertSignatureHelpActiveParameterEquals($fileName, 145, 147, 1);
    }

    /**
     * @return void
     */
    public function testFunctionCallWithMissingLastArgumentAfterComma(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(int $a, int $b)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('int $b', 'Parameter B.'),
            ]),
        ];

        $fileName = 'FunctionCallWithMissingLastArgumentAfterComma.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 142, 144, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 142, 143, 0);
        self::assertSignatureHelpActiveParameterEquals($fileName, 144, 144, 1);
    }

    /**
     * @return void
     */
    public function testFunctionCallDoesNotWorkWhenInsideClosureArgumentBody(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(Closure $a)', null, [
                new ParameterInformation('Closure $a', null),
            ]),
        ];

        $fileName = 'FunctionCallClosureArgumentBody.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 80, 104, $expectedSignaturesResult);

        for ($i = 105; $i <= 118; ++$i) {
            $hadException = false;

            self::assertNull(
                $this->getSignatureHelp($fileName, $i),
                'Signature help should not trigger inside the body of closure arguments'
            );
        }

        self::assertSignatureHelpSignaturesEquals($fileName, 119, 119, $expectedSignaturesResult);
    }

    /**
     * @return void
     */
    public function testFunctionCallDoesNotWorkWhenInsideArrowFunctionClosureArgumentBody(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('test(Closure $a)', null, [
                new ParameterInformation('Closure $a', null),
            ]),
        ];

        $fileName = 'FunctionCallArrowFunctionClosureArgumentBody.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 80, 88, $expectedSignaturesResult);

        for ($i = 89; $i <= 90; ++$i) {
            $hadException = false;

            self::assertNull(
                $this->getSignatureHelp($fileName, $i),
                'Signature help should not trigger inside the body of arrow function arguments'
            );
        }

        self::assertSignatureHelpSignaturesEquals($fileName, 91, 91, $expectedSignaturesResult);
    }

    /**
     * @return void
     */
    public function testNestedFunctionCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('foo(int $a)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
            ]),
        ];

        $expectedNestedSignaturesResult = [
            new SignatureInformation('bar()', null, []),
        ];

        $fileName = 'NestedFunctionCall.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 127, 131, $expectedSignaturesResult);
        self::assertSignatureHelpSignaturesEquals($fileName, 132, 134, $expectedNestedSignaturesResult);
        self::assertSignatureHelpSignaturesEquals($fileName, 135, 136, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 127, 131, 0);
        self::assertSignatureHelpActiveParameterEquals($fileName, 132, 134, null);
        self::assertSignatureHelpActiveParameterEquals($fileName, 135, 136, 0);
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
                new ParameterInformation('string $c', 'Parameter C.'),
            ]),
        ];

        $fileName = 'MethodCall.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 245, 252, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 245, 246, 0);
        self::assertSignatureHelpActiveParameterEquals($fileName, 247, 249, 1);
        self::assertSignatureHelpActiveParameterEquals($fileName, 250, 252, 2);
    }

    /**
     * @return void
     */
    public function testNestedMethodCall(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('foo(int $a)', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
            ]),
        ];

        $expectedNestedSignaturesResult = [
            new SignatureInformation('bar()', null, []),
        ];

        $fileName = 'NestedMethodCall.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 221, 232, $expectedSignaturesResult);
        self::assertSignatureHelpSignaturesEquals($fileName, 233, 235, $expectedNestedSignaturesResult);
        self::assertSignatureHelpSignaturesEquals($fileName, 236, 237, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 221, 232, 0);
        self::assertSignatureHelpActiveParameterEquals($fileName, 233, 235, null);
        self::assertSignatureHelpActiveParameterEquals($fileName, 236, 237, 0);
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
                new ParameterInformation('string $c', 'Parameter C.'),
            ]),
        ];

        $fileName = 'StaticMethodCall.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 253, 260, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 253, 254, 0);
        self::assertSignatureHelpActiveParameterEquals($fileName, 255, 257, 1);
        self::assertSignatureHelpActiveParameterEquals($fileName, 258, 260, 2);
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
                new ParameterInformation('string $c', 'Parameter C.'),
            ]),
        ];

        $fileName = 'Constructor.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 256, 309, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 256, 270, 0);
        self::assertSignatureHelpActiveParameterEquals($fileName, 271, 285, 1);
        self::assertSignatureHelpActiveParameterEquals($fileName, 286, 309, 2);
    }

    /**
     * @return void
     */
    public function testNestedConstructor(): void
    {
        $expectedSignaturesResult = [
            new SignatureInformation('__construct(int $a)', null, [
                new ParameterInformation('int $a', null),
            ]),
        ];

        $expectedNestedSignaturesResult = [
            new SignatureInformation('__construct(int $b)', null, [
                new ParameterInformation('int $b', null),
            ]),
        ];

        $fileName = 'NestedConstructor.phpt';

        self::assertSignatureHelpSignaturesEquals($fileName, 219, 227, $expectedSignaturesResult);
        self::assertSignatureHelpSignaturesEquals($fileName, 228, 231, $expectedNestedSignaturesResult);
        self::assertSignatureHelpSignaturesEquals($fileName, 232, 233, $expectedSignaturesResult);
        self::assertSignatureHelpActiveParameterEquals($fileName, 219, 233, 0);
    }

    /**
     * @return void
     */
    public function testArgumentIndexIsNullForFunctionCallFunctionWithoutArguments(): void
    {
        $result = $this->getSignatureHelp('FunctionCallFunctionWithoutArguments.phpt', 48);

        self::assertCount(1, $result->getSignatures());
        self::assertEmpty($result->getSignatures()[0]->getParameters());
        self::assertNull($result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testArgumentIndexIsCorrectWithVariadicParameters(): void
    {
        $result = $this->getSignatureHelp('VariadicParameter.phpt', 217);

        self::assertCount(1, $result->getSignatures());
        self::assertCount(2, $result->getSignatures()[0]->getParameters());
        self::assertSame(1, $result->getActiveParameter());

        $result = $this->getSignatureHelp('VariadicParameter.phpt', 220);

        self::assertCount(1, $result->getSignatures());
        self::assertCount(2, $result->getSignatures()[0]->getParameters());
        self::assertSame(1, $result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testFunctionCallFunctionNameDoesNotWorkWhenFunctionDoesNotHaveArguments(): void
    {
        self::assertNull($this->getSignatureHelp('FunctionCallFunctionNameWithoutArguments.phpt', 47));
    }

    /**
     * @return void
     */
    public function testFunctionCallFunctionNameDoesNotWorkWhenFunctionHasArguments(): void
    {
        self::assertNull($this->getSignatureHelp('FunctionCallFunctionNameWithArguments.phpt', 53));
    }

    /**
     * @return void
     */
    public function testFunctionCallFailsWhenArgumentIsOutOfBounds(): void
    {
        self::assertNull($this->getSignatureHelp('FunctionCallTooManyArguments.phpt', 98));
    }

    /**
     * @return void
     */
    public function testFunctionCallFailsWhenTrailingCommaIsPresentThatWouldBeFollowedByOutOfBoundsArgument(): void
    {
        self::assertNull(
            $this->getSignatureHelp('TrailingCommaIsPresentThatWouldBeFollowedByOutOfBoundsArgument.phpt', 97)
        );
    }

    /**
     * @return void
     */
    public function testDynamicFunctionNameFails(): void
    {
        self::assertNull($this->getSignatureHelp('FunctionCallDynamic.phpt', 49));
    }

    /**
     * @return void
     */
    public function testDynamicMethodNameFails(): void
    {
        self::assertNull($this->getSignatureHelp('MethodCallDynamic.phpt', 85));
    }

    /**
     * @return void
     */
    public function testNoInvocationWithMissingMemberNameFails(): void
    {
        self::assertNull($this->getSignatureHelp('NoInvocationMissingMember.phpt', 17));
    }

    /**
     * @param string $file
     * @param int    $position
     *
     * @return SignatureHelp|null
     */
    private function getSignatureHelp(string $file, int $position): ?SignatureHelp
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $code = $this->container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        return $this->container->get('signatureHelpRetriever')->get(
            new TextDocumentItem($path, $code),
            Position::createFromByteOffset($position, $code, PositionEncoding::VALUE)
        );
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/SignatureHelpTest/' . $file;
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

            self::assertNotNull($result, 'Expected signature help at offset ' . $i);
            self::assertSame(0, $result->getActiveSignature());
            self::assertEquals($signatures, $result->getSignatures());

            ++$i;
        }

        // Assert that the range doesn't extend longer than it should.
        $resultBeforeRange = $this->getSignatureHelp($fileName, $start - 1);

        self::assertTrue(
            !$resultBeforeRange || $resultBeforeRange->getSignatures() !== $signatures,
            "Range does not start exactly at position {$start}, but seems to continue before it"
        );

        $resultAfterRange = $this->getSignatureHelp($fileName, $end + 1);

        self::assertTrue(
            !$resultAfterRange || $resultAfterRange->getSignatures() !== $signatures,
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

            self::assertNotNull($result);
            self::assertSame($activeParameter, $result->getActiveParameter());

            ++$i;
        }

        // Assert that the range doesn't extend longer than it should.
        $resultBeforeRange = $this->getSignatureHelp($fileName, $start - 1);

        self::assertTrue(
            !$resultBeforeRange || $resultBeforeRange->getActiveParameter() !== $activeParameter,
            "Range does not start exactly at position {$start}, but seems to continue before it"
        );

        $resultAfterRange = $this->getSignatureHelp($fileName, $end + 1);

        self::assertTrue(
            !$resultAfterRange || $resultAfterRange->getActiveParameter() !== $activeParameter,
            "Range does not end exactly at position {$end}, but seems to continue after it"
        );
    }
}
