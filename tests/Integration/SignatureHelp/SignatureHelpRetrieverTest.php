<?php

namespace PhpIntegrator\Tests\Unit\SignatureHelp;

use PhpIntegrator\SignatureHelp\SignatureHelp;

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

        $this->assertCount(1, $result->getSignatures());
        $this->assertEquals('test', $result->getSignatures()[0]->getLabel());
        $this->assertEquals('Some summary.', $result->getSignatures()[0]->getDocumentation());
        $this->assertCount(3, $result->getSignatures()[0]->getParameters());
        $this->assertEquals('int $a', $result->getSignatures()[0]->getParameters()[0]->getLabel());
        $this->assertEquals('Parameter A.', $result->getSignatures()[0]->getParameters()[0]->getDocumentation());
        $this->assertEquals('bool $b = true', $result->getSignatures()[0]->getParameters()[1]->getLabel());
        $this->assertNull($result->getSignatures()[0]->getParameters()[1]->getDocumentation());
        $this->assertEquals('string $c', $result->getSignatures()[0]->getParameters()[2]->getLabel());
        $this->assertEquals('Parameter C.', $result->getSignatures()[0]->getParameters()[2]->getDocumentation());
        $this->assertEquals(0, $result->getActiveSignature());
        $this->assertEquals(2, $result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testMethodCall(): void
    {
        $result = $this->getSignatureHelp('MethodCall.phpt', 251);

        $this->assertCount(1, $result->getSignatures());
        $this->assertEquals('test', $result->getSignatures()[0]->getLabel());
        $this->assertEquals('Some summary.', $result->getSignatures()[0]->getDocumentation());
        $this->assertCount(3, $result->getSignatures()[0]->getParameters());
        $this->assertEquals('int $a', $result->getSignatures()[0]->getParameters()[0]->getLabel());
        $this->assertEquals('Parameter A.', $result->getSignatures()[0]->getParameters()[0]->getDocumentation());
        $this->assertEquals('bool $b = true', $result->getSignatures()[0]->getParameters()[1]->getLabel());
        $this->assertNull($result->getSignatures()[0]->getParameters()[1]->getDocumentation());
        $this->assertEquals('string $c', $result->getSignatures()[0]->getParameters()[2]->getLabel());
        $this->assertEquals('Parameter C.', $result->getSignatures()[0]->getParameters()[2]->getDocumentation());
        $this->assertEquals(0, $result->getActiveSignature());
        $this->assertEquals(2, $result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testStaticMethodCall(): void
    {
        $result = $this->getSignatureHelp('StaticMethodCall.phpt', 259);

        $this->assertCount(1, $result->getSignatures());
        $this->assertEquals('test', $result->getSignatures()[0]->getLabel());
        $this->assertEquals('Some summary.', $result->getSignatures()[0]->getDocumentation());
        $this->assertCount(3, $result->getSignatures()[0]->getParameters());
        $this->assertEquals('int $a', $result->getSignatures()[0]->getParameters()[0]->getLabel());
        $this->assertEquals('Parameter A.', $result->getSignatures()[0]->getParameters()[0]->getDocumentation());
        $this->assertEquals('bool $b = true', $result->getSignatures()[0]->getParameters()[1]->getLabel());
        $this->assertNull($result->getSignatures()[0]->getParameters()[1]->getDocumentation());
        $this->assertEquals('string $c', $result->getSignatures()[0]->getParameters()[2]->getLabel());
        $this->assertEquals('Parameter C.', $result->getSignatures()[0]->getParameters()[2]->getDocumentation());
        $this->assertEquals(0, $result->getActiveSignature());
        $this->assertEquals(2, $result->getActiveParameter());
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $result = $this->getSignatureHelp('Constructor.phpt', 300);

        $this->assertCount(1, $result->getSignatures());
        $this->assertEquals('__construct', $result->getSignatures()[0]->getLabel());
        $this->assertEquals('Some summary.', $result->getSignatures()[0]->getDocumentation());
        $this->assertCount(3, $result->getSignatures()[0]->getParameters());
        $this->assertEquals('int $a', $result->getSignatures()[0]->getParameters()[0]->getLabel());
        $this->assertEquals('Parameter A.', $result->getSignatures()[0]->getParameters()[0]->getDocumentation());
        $this->assertEquals('bool $b = true', $result->getSignatures()[0]->getParameters()[1]->getLabel());
        $this->assertNull($result->getSignatures()[0]->getParameters()[1]->getDocumentation());
        $this->assertEquals('string $c', $result->getSignatures()[0]->getParameters()[2]->getLabel());
        $this->assertEquals('Parameter C.', $result->getSignatures()[0]->getParameters()[2]->getDocumentation());
        $this->assertEquals(0, $result->getActiveSignature());
        $this->assertEquals(2, $result->getActiveParameter());
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testReturnsNullWhenNotInInvocation1(): void
    {
        $result = $this->getSignatureHelp('NoInvocation.phpt', 233);
    }
}
