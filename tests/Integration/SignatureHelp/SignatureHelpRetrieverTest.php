<?php

namespace PhpIntegrator\Tests\Unit\SignatureHelp;

use PhpIntegrator\SignatureHelp\SignatureHelp;
use PhpIntegrator\SignatureHelp\ParameterInformation;
use PhpIntegrator\SignatureHelp\SignatureInformation;

use PhpIntegrator\Tests\Integration\AbstractIndexedTest;

class SignatureHelpRetrieverTest extends AbstractIndexedTest
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
     * @return void
     */
    public function testFunctionCall(): void
    {
        $result = $this->getSignatureHelp('FunctionCall.phpt', 185);

        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $this->assertEquals($expectedSignaturesResult, $result->getSignatures());
        $this->assertEquals(0, $result->getActiveSignature());
        $this->assertEquals(2, $result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testFunctionCallBeforeFirstAndOnlyArgumentInWhitespace(): void
    {
        $result = $this->getSignatureHelp('FunctionCallBeforeFirstAndOnlyArgumentInWhitespace.phpt', 108);

        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.')
            ])
        ];

        $this->assertEquals($expectedSignaturesResult, $result->getSignatures());

        $this->assertEquals(0, $result->getActiveSignature());
        $this->assertEquals(0, $result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testMethodCall(): void
    {
        $result = $this->getSignatureHelp('MethodCall.phpt', 251);

        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $this->assertEquals($expectedSignaturesResult, $result->getSignatures());
        $this->assertEquals(0, $result->getActiveSignature());
        $this->assertEquals(2, $result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testStaticMethodCall(): void
    {
        $result = $this->getSignatureHelp('StaticMethodCall.phpt', 259);

        $expectedSignaturesResult = [
            new SignatureInformation('test', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $this->assertEquals($expectedSignaturesResult, $result->getSignatures());
        $this->assertEquals(0, $result->getActiveSignature());
        $this->assertEquals(2, $result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $result = $this->getSignatureHelp('Constructor.phpt', 300);

        $expectedSignaturesResult = [
            new SignatureInformation('__construct', 'Some summary.', [
                new ParameterInformation('int $a', 'Parameter A.'),
                new ParameterInformation('bool $b = true', null),
                new ParameterInformation('string $c', 'Parameter C.')
            ])
        ];

        $this->assertEquals($expectedSignaturesResult, $result->getSignatures());
        $this->assertEquals(0, $result->getActiveSignature());
        $this->assertEquals(2, $result->getActiveParameter());
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
    public function testNoInvocation(): void
    {
        $result = $this->getSignatureHelp('NoInvocation.phpt', 233);
    }
}
