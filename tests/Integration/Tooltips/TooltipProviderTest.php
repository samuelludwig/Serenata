<?php

namespace PhpIntegrator\Tests\Integration\Tooltips;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use PhpIntegrator\Tooltips\TooltipResult;

class TooltipProviderTest extends AbstractIntegrationTest
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
     *
     * @return void
     */
    protected function assertTooltipEquals(string $fileName, int $start, int $end, string $contents): void
    {
        $i = $start;

        while ($i <= $end) {
            $result = $this->getTooltip($fileName, $i);

            $this->assertNotNull($result);
            $this->assertNull($result->getRange());
            $this->assertEquals($result->getContents(), $contents);

            ++$i;
        }

        // Assert that the range doesn't extend longer than it should.
        $resultBeforeRange = $this->getTooltip($fileName, $start - 1);
        $this->assertTrue(
            $resultBeforeRange === null || $resultBeforeRange->getContents() !== $contents,
            "Range does not start exactly at position {$start}, but seems to continue before it"
        );

        $resultAfterRange = $this->getTooltip($fileName, $end + 1);
        $this->assertTrue(
            $resultAfterRange === null || $resultAfterRange->getContents() !== $contents,
            "Range does not end exactly at position {$end}, but seems to continue after it"
        );
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
**•&nbsp;$first** | *string* | Testdescription
**•&nbsp;$second** | *int* | Test
**•&nbsp;$third** | *\Exception* | Test

# Returns
*string&#124;bool*

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
*string&#124;bool*');
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

        $this->assertTooltipEquals('ClassNameAllSections.phpt', 390, 404, '(No documentation available)

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
        $this->assertTooltipEquals('ClassNameUseImport.phpt', 83, 85, 'This is a summary.

# Full Name
*\A\B*

# Type
Class');
    }

    /**
     * @return void
     */
    public function testClassNameInImplementsClause(): void
    {
        $this->assertTooltipEquals('ClassNameImplements.phpt', 82, 82, 'This is a summary.

# Full Name
*\A\B*

# Type
Class');
    }

    /**
     * @return void
     */
    public function testClassNameInExtendsClause(): void
    {
        $this->assertTooltipEquals('ClassNameExtends.phpt', 79, 79, 'This is a summary.

# Full Name
*\A\B*

# Type
Class');
    }

    /**
     * @return void
     */
    public function testClassNameInTraitUse(): void
    {
        $this->assertTooltipEquals('ClassNameTraitUse.phpt', 81, 81, 'This is a summary.

# Full Name
*\A\B*

# Type
Trait');
    }

    /**
     * @return void
     */
    public function testClassNameInTraitAlias(): void
    {
        $this->assertTooltipEquals('ClassNameTraitAlias.phpt', 123, 123, 'This is a summary.

# Full Name
*\A\B*

# Type
Trait');
    }

    /**
     * @return void
     */
    public function testClassNameInTraitPrecedence(): void
    {
        $this->assertTooltipEquals('ClassNameTraitPrecedence.phpt', 165, 165, 'This is a summary.

# Full Name
*\A\B*

# Type
Trait');
    }

    /**
     * @return void
     */
    public function testClassNameAfterNewKeyword(): void
    {
        $this->assertTooltipEquals('ClassNameNewKeyword.phpt', 72, 72, 'This is a summary.

# Full Name
*\A\B*

# Type
Class');
    }

    /**
     * @return void
     */
    public function testFunctionDefinition(): void
    {
        $this->assertTooltipEquals('FunctionDefinition.phpt', 37, 56, 'This is a summary.

# Returns
*void*');
    }

    /**
     * @return void
     */
    public function testClassMethodDefinition(): void
    {
        $this->assertTooltipEquals('ClassMethodDefinition.phpt', 63, 97, 'This is a summary.

# Returns
*void*');
    }

    /**
     * @return void
     */
    public function testMethodCall(): void
    {
        $this->assertTooltipEquals('MethodCall.phpt', 111, 118, 'This is a summary.

# Returns
*void*');
    }

    /**
     * @return void
     */
    public function testStaticMethodCall(): void
    {
        $this->assertTooltipEquals('StaticMethodCall.phpt', 103, 110, 'This is a summary.

# Returns
*void*');
    }

    /**
     * @return void
     */
    public function testPropertyFetchContainsAllSections(): void
    {
        $this->assertTooltipEquals('PropertyFetchAllSections.phpt', 191, 196, 'Hi! *Bold text* **Italic** ~~Strikethrough~~

# Description
## Header
Hello!

# Type
*string&#124;bool*');
    }

    /**
     * @return void
     */
    public function testStaticPropertyFetch(): void
    {
        $this->assertTooltipEquals('StaticPropertyFetch.phpt', 88, 94, 'This is a summary.

# Type
(Not known)');
    }

    /**
     * @return void
     */
    public function testFunctionDefinitionRangeIsConfinedToBeforeFirstStatement(): void
    {
        $this->assertTooltipEquals('FunctionDefinitionWithStatement.phpt', 37, 58, 'This is a summary.

# Returns
*void*');
    }

    /**
     * @return void
     */
    public function testFunctionDefinitionRangeIsConfinedToBeforeFirstParameter(): void
    {
        $fileName = 'FunctionDefinitionWithParameter.phpt';

        $this->assertTooltipEquals($fileName, 37, 55, 'This is a summary.

# Parameters
   |   |   ' . '
--- | --- | ---
**•&nbsp;$param1** | *int* |  ' . '
**•&nbsp;$param2** | *bool* |  ' . '

# Returns
*void*');

        $this->assertNull($this->getTooltip($fileName, 69));
    }

    /**
     * @return void
     */
    public function testFunctionDefinitionRangeIsConfinedToNonScalarReturnType(): void
    {
        $this->assertTooltipEquals('FunctionDefinitionWithReturnType.phpt', 37, 53, 'This is a summary.

# Returns
*void*');
    }
}
