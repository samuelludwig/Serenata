<?php

namespace PhpIntegrator\Tests\Tooltips;

use PhpIntegrator\Tests\IndexedTest;

use PhpIntegrator\Tooltips\TooltipResult;

class TooltipProviderTest extends IndexedTest
{
    /**
     * @param string $file
     * @param int    $position
     *
     * @return TooltipResult|null
     */
    protected function getTooltip(string $file, int $position): ?TooltipResult
    {
        $path = $this->getPathFor($file);

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path);

        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        return $container->get('tooltipProvider')->get($code, $position);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getPathFor(string $file): string
    {
        return __DIR__ . '/TooltipProviderTest/' . $file;
    }

    /**
     * @param string $fileName
     * @param int    $start
     * @param int    $end
     * @param string $contents
     */
    protected function assertTooltipEquals(string $fileName, int $start, int $end, string $contents)
    {
        while ($start <= $end) {
            $result = $this->getTooltip($fileName, $start);

            $this->assertNotNull($result);
            $this->assertNull($result->getRange());
            $this->assertEquals($result->getContents(), $contents);

            ++$start;
        }
    }

    /**
     * @return void
     */
    public function testFuncCallContainsAllSections(): void
    {
        $this->assertTooltipEquals('FuncCallAllSections.phpt', 436, 439, 'Hi! *Bold text* **Italic** ~~Strikethrough~~

# Description
## Header
Hello!

# Parameters
   |   |   ' . '
--- | --- | ---
**• $first** | *string* | Testdescription
**• $second** | *int* | Test
**• $third** | *\Exception* | Test

# Returns
*string|bool*

# Throws
   |   |   ' . '
--- | --- | ---
• **\Exception** | When something happens
• **\LogicException** | When something else happens.
• **\RuntimeException** |  ');
    }

    /**
     * @return void
     */
    public function testUnqualifiedFunctionCall(): void
    {
        $this->assertTooltipEquals('UnqualifiedFuncCall.phpt', 58, 61, "This is a summary.\n\n# Returns\n*void*");
    }

    /**
     * @return void
     */
    public function testQualifiedFunctionCall(): void
    {
        $this->assertTooltipEquals('QualifiedFuncCall.phpt', 88, 93, "This is a summary.\n\n# Returns\n*void*");
    }

    /**
     * @return void
     */
    public function testFullyQualifiedFunctionCall(): void
    {
        $this->assertTooltipEquals('FullyQualifiedFuncCall.phpt', 88, 96, "This is a summary.\n\n# Returns\n*void*");
    }

    // /**
    //  * @return void
    //  */
    // public function testConstFetchContainsAllSections(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testUnqualifiedConstFetch(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testQualifiedConstFetch(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testFullyQualifiedConstFetch(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassConstFetch(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameContainsAllSections(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInClassConstFetch(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInStaticMethodCall(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInStaticPropertyFetch(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInUseStatemen(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInImplementsClause(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInExtendsClause(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInTraitUse(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInTraitAlias(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInTraitPrecedence(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameAfterNewKeyword(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassNameInClassConstFetch(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testFunctionDefinition(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testMethodCall(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testStaticMethodCall(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testPropertyFetchContainsAllSections(): void
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
    // public function testStaticPropertyFetch(): void
    // {
    //     // TODO
    // }
}
