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
        $result = $this->getTooltip($fileName, 410);

        while ($start < $end) {
            $this->assertNotNull($result);
            $this->assertNull($result->getRange());
            $this->assertEquals($result->getContents(), $contents);

            ++$start;
        }
    }

    /**
     * @return void
     */
    public function testUnqualifiedFunctionCall(): void
    {
        $this->assertTooltipEquals('GlobalFunction.phpt', 410, 413, 'Hi! *Bold text* **Italic** ~~Strikethrough~~

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
*void*

# Throws
   |   |   ' . '
--- | --- | ---
• **\Exception** | When something happens
• **\LogicException** | When something else happens.
• **\RuntimeException** |  ');
    }

    // /**
    //  * @return void
    //  */
    // public function testQualifiedFunction(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testFullyQualifiedFunction(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClassConstant(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testClass(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testConstant(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testQualifiedConstant(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testFullyQualifiedConstant(): void
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
    // public function testMethod(): void
    // {
    //     // TODO
    // }
    //
    // /**
    //  * @return void
    //  */
    // public function testProperty(): void
    // {
    //     // TODO
    // }
}
