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

        return $container->get('tooltipProvider')->get($path, $code, $position);
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
        $this->assertTooltipEquals('FuncCallAllSections.phpt', 435, 440, 'Hi! *Bold text* **Italic** ~~Strikethrough~~

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
        $this->assertTooltipEquals('UnqualifiedFuncCall.phpt', 57, 62, "This is a summary.\n\n# Returns\n*void*");
    }

    /**
     * @return void
     */
    public function testQualifiedFunctionCall(): void
    {
        $this->assertTooltipEquals('QualifiedFuncCall.phpt', 87, 94, "This is a summary.\n\n# Returns\n*void*");
    }

    /**
     * @return void
     */
    public function testFullyQualifiedFunctionCall(): void
    {
        $this->assertTooltipEquals('FullyQualifiedFuncCall.phpt', 87, 97, "This is a summary.\n\n# Returns\n*void*");
    }

    /**
     * @return void
     */
    public function testConstFetchContainsAllSections(): void
    {
        $this->assertTooltipEquals('ConstFetchAllSections.phpt', 134, 137, 'Hi! *Bold text* **Italic** ~~Strikethrough~~

# Description
## Header
Hello!

# Type
*string|bool*');
    }

    /**
     * @return void
     */
    public function testUnqualifiedConstFetch(): void
    {
        $this->assertTooltipEquals('UnqualifiedConstFetch.phpt', 59, 62, "This is a summary.\n\n# Type\n*int*");
    }

    /**
     * @return void
     */
    public function testQualifiedConstFetch(): void
    {
        $this->assertTooltipEquals('QualifiedConstFetch.phpt', 89, 94, "This is a summary.\n\n# Type\n*int*");
    }

    /**
     * @return void
     */
    public function testFullyQualifiedConstFetch(): void
    {
        $this->assertTooltipEquals('FullyQualifiedConstFetch.phpt', 89, 97, "This is a summary.\n\n# Type\n*int*");
    }

    /**
     * @return void
     */
    public function testClassConstFetch(): void
    {
        $this->assertTooltipEquals('ClassConstFetch.phpt', 106, 115, "This is a summary.\n\n# Type\n*int*");
    }

    /**
     * @return void
     */
    public function testClassNameContainsAllSections(): void
    {
        $this->assertTooltipEquals('ClassNameAllSections.phpt', 316, 326, 'Hi! *Bold text* **Italic** ~~Strikethrough~~

# Description
## Header
Hello!

# Full Name
*\A\SimpleClass*

# Type
Class');

        $this->assertTooltipEquals('ClassNameAllSections.phpt', 340, 352, '(No documentation available)

# Full Name
*\A\AbstractClass*

# Type
Abstract class');

        $this->assertTooltipEquals('ClassNameAllSections.phpt', 366, 376, '(No documentation available)

# Full Name
*\A\SimpleTrait*

# Type
Trait');

        $this->assertTooltipEquals('ClassNameAllSections.phpt', 390, 402, '(No documentation available)

# Full Name
*\A\SimpleInterface*

# Type
Interface');
    }

    /**
     * @return void
     */
    public function testClassNameInClassConstFetch(): void
    {
        $this->assertTooltipEquals('ClassNameClassConstFetch.phpt', 93, 93, 'This is a summary.

# Full Name
*\A\B*

# Type
Class');
    }

    /**
     * @return void
     */
    public function testClassNameInStaticMethodCall(): void
    {
        $this->assertTooltipEquals('ClassNameStaticMethodCall.phpt', 106, 106, 'This is a summary.

# Full Name
*\A\B*

# Type
Class');
    }

    /**
     * @return void
     */
    public function testClassNameInStaticPropertyFetch(): void
    {
        $this->assertTooltipEquals('ClassNameStaticPropertyFetch.phpt', 86, 86, 'This is a summary.

# Full Name
*\A\B*

# Type
Class');
    }

    /**
     * @return void
     */
    public function testClassNameInUseImport(): void
    {
        $this->assertTooltipEquals('ClassNameUseImport.phpt', 84, 84, 'This is a summary.

# Full Name
*\A\B*

# Type
Class');
    }

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