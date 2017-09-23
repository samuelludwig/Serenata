<?php

namespace PhpIntegrator\Tests\Unit\SignatureHelp;

use UnexpectedValueException;

use PhpIntegrator\GotoDefinition\GotoDefinitionResult;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class DefinitionLocatorTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testFunctionCall(): void
    {
        $fileName = 'FunctionCall.phpt';

        $this->assertGotoDefinitionResultEquals(
            $fileName,
            43,
            48,
            new GotoDefinitionResult($this->getPathFor($fileName), 5)
        );
    }

    /**
     * @return void
     */
    public function testMethodCall(): void
    {
        $fileName = 'MethodCall.phpt';

        $this->assertGotoDefinitionResultEquals(
            $fileName,
            76,
            79,
            new GotoDefinitionResult($this->getPathFor($fileName), 7)
        );
    }

    /**
     * @return void
     */
    public function testConstant(): void
    {
        $fileName = 'Constant.phpt';

        $this->assertGotoDefinitionResultEquals(
            $fileName,
            45,
            47,
            new GotoDefinitionResult($this->getPathFor($fileName), 5)
        );
    }

    // /**
    //  * @return void
    //  */
    // public function testClassConstantConstant(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassConstantClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testStaticMethodCallMethod(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testStaticMethodCallClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testPropertyFetch(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testStaticPropertyFetchProperty(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testStaticPropertyFetchClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testUseStatementClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testGroupedUseStatementClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testImplementsClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testExtendsClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testTraitUseClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testTraitUseInsteadofClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testTraitUseAliasClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testCommentClassAfterVarTag(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testCommentClassAfterParamTag(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testCommentClassAfterReturnTag(): void
    // {
    //     // TODO
    // }

    /**
     * @param string $file
     * @param int    $position
     *
     * @return GotoDefinitionResult|null
     */
    protected function locateDefinition(string $file, int $position): ?GotoDefinitionResult
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $code = $this->container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        $file = $this->container->get('storage')->getFileByPath($path);

        return $this->container->get('definitionLocator')->locate($file, $code, $position);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/DefinitionLocatorTest/' . $file;
    }

    /**
     * @param string               $fileName
     * @param int                  $start
     * @param int                  $end
     * @param GotoDefinitionResult $gotoDefinitionResult
     */
    protected function assertGotoDefinitionResultEquals(
        string $fileName,
        int $start,
        int $end,
        GotoDefinitionResult $gotoDefinitionResult
    ): void {
        $i = $start;

        while ($i <= $end) {
            $result = $this->locateDefinition($fileName, $i);

            $this->assertNotNull($result, 'Failed locating definition at offset ' . $i);
            $this->assertSame($gotoDefinitionResult->getUri(), $result->getUri());
            $this->assertSame($gotoDefinitionResult->getLine(), $result->getLine());

            ++$i;
        }

        // Assert that the range doesn't extend longer than it should.
        $gotException = false;

        try {
            $resultBeforeRange = $this->locateDefinition($fileName, $start - 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        $this->assertTrue(
            $gotException === true ||
            $resultBeforeRange === null ||
            ($gotException === false && (
                $resultBeforeRange->getUri() !== $gotoDefinitionResult->getUri() ||
                $resultBeforeRange->getLine() !== $gotoDefinitionResult->getLine()
            )),
            "Range does not start exactly at position {$start}, but seems to continue before it"
        );

        $gotException = false;

        try {
            $resultAfterRange = $this->locateDefinition($fileName, $end + 1);
        } catch (UnexpectedValueException $e) {
            $gotException = true;
        }

        $this->assertTrue(
            $gotException === true ||
            $resultAfterRange == null ||
            ($gotException === false && (
                $resultAfterRange->getUri() !== $gotoDefinitionResult->getUri() ||
                $resultAfterRange->getLine() !== $gotoDefinitionResult->getLine()
            )),
            "Range does not end exactly at position {$end}, but seems to continue after it"
        );
    }
}
