<?php

namespace PhpIntegrator\Tests\Integration\UserInterface\Command;

use PhpIntegrator\Linting\LintingSettings;

use PhpIntegrator\Tests\Integration\AbstractIndexedTest;

class LinterTest extends AbstractIndexedTest
{
    /**
     * @param string $file
     * @param bool   $indexingMayFail
     *
     * @return array
     */
    protected function lintFile(string $file, bool $indexingMayFail = false): array
    {
        $path = __DIR__ . '/LinterTest/' . $file;

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path, $indexingMayFail);

        $linter = $container->get('linter');

        $settings = new LintingSettings(
            true,
            true,
            true,
            true,
            true,
            true
        );

        return $linter->lint($path, file_get_contents($path), $settings);
    }

    /**
     * @return void
     */
    public function testCorrectlyIdentifiesSyntaxErrors(): void
    {
        $output = $this->lintFile('SyntaxError.phpt', true);

        $this->assertEquals(2, count($output['errors']['syntaxErrors']));
    }

    /**
     * @return void
     */
    public function testReportsUnknownClassesWithNoNamespace(): void
    {
        $output = $this->lintFile('UnknownClassesNoNamespace.phpt');

        $this->assertEquals([
            [
                'name'      => 'A\B',
                'namespace' => null,
                'start'     => 32,
                'end'       => 35
            ]
        ], $output['errors']['unknownClasses']);
    }

    /**
     * @return void
     */
    public function testReportsUnknownClassesWithSingleNamespace(): void
    {
        $output = $this->lintFile('UnknownClassesSingleNamespace.phpt');

        $this->assertEquals([
            [
                'name'      => 'DateTime',
                'namespace' => 'A',
                'start'     => 64,
                'end'       => 72
            ],
            [
                'name'      => 'DateTimeZone',
                'namespace' => 'A',
                'start'     => 85,
                'end'       => 97
            ]
        ], $output['errors']['unknownClasses']);
    }

    /**
     * @return void
     */
    public function testReportsUnknownClassesWithMultipleNamespaces(): void
    {
        $output = $this->lintFile('UnknownClassesMultipleNamespaces.phpt');

        $this->assertEquals([
            [
                'name'      => 'DateTime',
                'namespace' => 'A',
                'start'     => 97,
                'end'       => 105
            ],

            [
                'name'      => 'SplFileInfo',
                'namespace' => 'B',
                'start'     => 153,
                'end'       => 164
            ]
        ], $output['errors']['unknownClasses']);
    }

    /**
     * @return void
     */
    public function testReportsUnknownClassesInDocBlocks(): void
    {
        $output = $this->lintFile('UnknownClassesDocblock.phpt');

        $this->assertEquals([
            [
                'name'      => 'A\B',
                'namespace' => 'A',
                'start'     => 75,
                'end'       => 95
            ],

            [
                'name'      => 'A\C',
                'namespace' => 'A',
                'start'     => 75,
                'end'       => 95
            ],

            [
                'name'      => 'MissingAnnotationClass',
                'namespace' => 'A',
                'start'     => 175,
                'end'       => 197
            ],

            [
                'name'      => 'A\MissingAnnotationClass',
                'namespace' => 'A',
                'start'     => 202,
                'end'       => 226
            ],

            [
                'name'      => 'B\MissingAnnotationClass',
                'namespace' => 'A',
                'start'     => 231,
                'end'       => 256
            ]
        ], $output['errors']['unknownClasses']);
    }

    /**
     * @return void
     */
    public function testDoesNotComplainAboutUnknownClassesInGroupedUseStatements(): void
    {
        $output = $this->lintFile('GroupedUseStatements.phpt');

        $this->assertEquals([], $output['errors']['unknownClasses']);
    }

    /**
     * @return void
     */
    public function testReportsInvalidMemberCallsOnAnExpressionWithoutAType(): void
    {
        $output = $this->lintFile('UnknownMemberExpressionWithNoType.phpt');

        $this->assertEquals([
            [
                'memberName' => 'foo',
                'start'      => 21,
                'end'        => 32
            ]
        ], $output['errors']['unknownMembers']['expressionHasNoType']);
    }

    /**
     * @return void
     */
    public function testReportsInvalidMemberCallsOnAnExpressionThatDoesNotReturnAClasslike(): void
    {
        $output = $this->lintFile('UnknownMemberExpressionWithNoClasslike.phpt');

        $this->assertEquals([
            [
                'memberName'     => 'foo',
                'expressionType' => 'int',
                'start'          => 57,
                'end'            => 68
            ],

            [
                'memberName'     => 'foo',
                'expressionType' => 'bool',
                'start'          => 57,
                'end'            => 68
            ]
        ], $output['errors']['unknownMembers']['expressionIsNotClasslike']);
    }

    /**
     * @return void
     */
    public function testReportsInvalidMemberCallsOnAnExpressionThatReturnsAClasslikeWithNoSuchMember(): void
    {
        $output = $this->lintFile('UnknownMemberExpressionWithNoSuchMember.phpt');

        $this->assertEquals([
            [
                'memberName'     => 'foo',
                'expressionType' => '\A\Foo',
                'start'          => 124,
                'end'            => 135
            ],

            [
                'memberName'     => 'bar',
                'expressionType' => '\A\Foo',
                'start'          => 137,
                'end'            => 147
            ],

            [
                'memberName'     => 'CONSTANT',
                'expressionType' => '\A\Foo',
                'start'          => 187,
                'end'            => 200
            ]
        ], $output['errors']['unknownMembers']['expressionHasNoSuchMember']);
    }

    /**
     * @return void
     */
    public function testReportsInvalidMemberCallsOnAnExpressionThatReturnsAClasslikeWithNoSuchMemberCausingANewMemberToBeCreated(): void
    {
        $output = $this->lintFile('UnknownMemberExpressionWithNoSuchMember.phpt');

        $this->assertEquals([
            [
                'memberName'     => 'test',
                'expressionType' => '\A\Foo',
                'start'          => 80,
                'end'            => 91
            ],

            [
                'memberName'     => 'fooProp',
                'expressionType' => '\A\Foo',
                'start'          => 149,
                'end'            => 162
            ],

            [
                'memberName'     => 'barProp',
                'expressionType' => '\A\Foo',
                'start'          => 168,
                'end'            => 181
            ]
        ], $output['warnings']['unknownMembers']['expressionNewMemberWillBeCreated']);
    }

    /**
     * @return void
     */
    public function testReportsUnknownGlobalFunctions(): void
    {
        $output = $this->lintFile('UnknownGlobalFunctions.phpt');

        $this->assertEquals([
            [
                'name'  => '\foo',
                'start' => 151,
                'end'   => 156
            ],

            [
                'name'  => '\foo',
                'start' => 162,
                'end'   => 168
            ],

            [
                'name'  => '\A\foo',
                'start' => 174,
                'end'   => 182
            ]
        ], $output['errors']['unknownGlobalFunctions']);
    }

    /**
     * @return void
     */
    public function testReportsUnknownGlobalConstants(): void
    {
        $output = $this->lintFile('UnknownGlobalConstants.phpt');

        $this->assertEquals([
            [
                'name'  => '\MISSING',
                'start' => 98,
                'end'   => 105
            ],

            [
                'name'  => '\MISSING',
                'start' => 111,
                'end'   => 119
            ],

            [
                'name'  => '\A\MISSING',
                'start' => 125,
                'end'   => 135
            ]
        ], $output['errors']['unknownGlobalConstants']);
    }

    /**
     * @return void
     */
    public function testReportsUnusedUseStatementsWithSingleNamespace(): void
    {
        $output = $this->lintFile('UnusedUseStatementsSingleNamespace.phpt');

        $this->assertEquals([
            [
                'name'  => 'Traversable',
                'alias' => 'Traversable',
                'start' => 39,
                'end'   => 50
            ]
        ], $output['warnings']['unusedUseStatements']);
    }

    /**
     * @return void
     */
    public function testReportsUnusedUseStatementsWithMultipleNamespaces(): void
    {
        $output = $this->lintFile('UnusedUseStatementsMultipleNamespaces.phpt');

        $this->assertEquals([
            [
                'name'  => 'SplFileInfo',
                'alias' => 'SplFileInfo',
                'start' => 47,
                'end'   => 58
            ],

            [
                'name'  => 'DateTime',
                'alias' => 'DateTime',
                'start' => 111,
                'end'   => 119
            ]
        ], $output['warnings']['unusedUseStatements']);
    }

    /**
     * @return void
     */
    public function testReportsUnusedUseStatementsWithGroupedUseStatements(): void
    {
        $output = $this->lintFile('GroupedUseStatements.phpt');

        $this->assertEquals([
            [
                'name'  => 'B\Foo',
                'alias' => 'Foo',
                'start' => 106,
                'end'   => 109
            ],

            [
                'name'  => 'B\Bar',
                'alias' => 'Bar',
                'start' => 119,
                'end'   => 122
            ],

            [
                'name'  => 'B\Missing',
                'alias' => 'Missing',
                'start' => 132,
                'end'   => 139
            ]
        ], $output['warnings']['unusedUseStatements']);
    }

    /**
     * @return void
     */
    public function testReportsUnusedUseStatementsForConstants(): void
    {
        $output = $this->lintFile('UnusedUseStatementsConstant.phpt');

        $this->assertEquals([
            [
                'name'  => 'Some\CONSTANT_UNUSED',
                'alias' => 'CONSTANT_UNUSED',
                'start' => 56,
                'end'   => 76
            ]
        ], $output['warnings']['unusedUseStatements']);
    }

    /**
     * @return void
     */
    public function testReportsUnusedUseStatementsForFunctions(): void
    {
        $output = $this->lintFile('UnusedUseStatementsFunction.phpt');

        $this->assertEquals([
            [
                'name'  => 'Some\funcUnused',
                'alias' => 'funcUnused',
                'start' => 58,
                'end'   => 73
            ]
        ], $output['warnings']['unusedUseStatements']);
    }

    /**
     * @return void
     */
    public function testSeesUseStatementsAsUsedIfTheyAppearInComments(): void
    {
        $output = $this->lintFile('UnusedUseStatementsDocblock.phpt');

        $this->assertEquals([
            [
                'name'  => 'SplMinHeap',
                'alias' => 'SplMinHeap',
                'start' => 53,
                'end'   => 63
            ],

            [
                'name'  => 'SplFileInfo',
                'alias' => 'SplFileInfo',
                'start' => 69,
                'end'   => 80
            ]
        ], $output['warnings']['unusedUseStatements']);
    }

    /**
     * @return void
     */
    public function testSeesUseStatementsAsUsedIfTheyAppearInAnonymousClasses(): void
    {
        $output = $this->lintFile('UnusedUseStatementsAnonymousClass.phpt');

        $this->assertEquals([], $output['warnings']['unusedUseStatements']);
    }

    /**
     * @return void
     */
    public function testCorrectlyIdentifiesMissingDocumentation(): void
    {
        $output = $this->lintFile('DocblockCorrectnessMissingDocumentation.phpt');

        $this->assertEquals([
            [
                'name'  => 'someMethod',
                'line'  => 41,
                'start' => 448,
                'end'   => 449
            ],

            [
                'name'  => 'someProperty',
                'line'  => 33,
                'start' => 331,
                'end'   => 344
            ],

            [
                'name'  => 'SOME_CONST',
                'line'  => 31,
                'start' => 300,
                'end'   => 310
            ],

            [
                'name'  => 'MissingDocumentation',
                'line'  => 47,
                'start' => 496,
                'end'   => 497
            ],

            [
                'name'  => 'some_function',
                'line'  => 5,
                'start' => 21,
                'end'   => 22
            ]
        ], $output['warnings']['docblockIssues']['missingDocumentation']);
    }

    /**
     * @return void
     */
    public function testCorrectlyIdentifiesDocblockMissingParameter(): void
    {
        $output = $this->lintFile('DocblockCorrectnessMissingParameter.phpt');

        $this->assertEquals([
            [
                'name'      => 'some_function_missing_parameter',
                'line'      => 17,
                'start'     => 186,
                'end'       => 187,
                'parameter' => '$param2'
            ]
        ], $output['errors']['docblockIssues']['parameterMissing']);
    }

    /**
     * @return void
     */
    public function testDoesNotComplainAboutMissingParameterWhenItIsAReference(): void
    {
        $output = $this->lintFile('DocblockCorrectnessParamWithReference.phpt');

        $this->assertEquals([

        ], $output['errors']['docblockIssues']['parameterMissing']);
    }

    /**
     * @return void
     */
    public function testDoesNotComplainAboutMissingParameterWhenItIsVariadic(): void
    {
        $output = $this->lintFile('DocblockCorrectnessVariadicParam.phpt');

        $this->assertEquals([

        ], $output['errors']['docblockIssues']['parameterMissing']);
    }

    /**
     * @return void
     */
    public function testDoesNotComplainAboutDocblocksHavingFullInheritance(): void
    {
        $output = $this->lintFile('DocblockCorrectnessFullInheritance.phpt');

        $this->assertEquals([

        ], $output['errors']['docblockIssues']['parameterMissing']);
    }

    /**
     * @return void
     */
    public function testCorrectlyIdentifiesDocblockParameterTypeMismatch(): void
    {
        $output = $this->lintFile('DocblockCorrectnessParameterTypeMismatch.phpt');

        $this->assertEquals([
            [
                'name'      => 'some_function_parameter_incorrect_type',
                'line'      => 21,
                'start'     => 341,
                'end'       => 342,
                'parameter' => '$param1'
            ],
        ], $output['errors']['docblockIssues']['parameterTypeMismatch']);
    }

    /**
     * @return void
     */
    public function testHighlightsReferenceParameterWithDocblockParameterMismatch(): void
    {
        $output = $this->lintFile('DocblockCorrectnessReferenceParam.phpt');

        $this->assertEquals([
            [
                'name'      => 'some_function_parameter_incorrect_type',
                'line'      => 8,
                'start'     => 57,
                'end'       => 58,
                'parameter' => '$param1'
            ]
        ], $output['errors']['docblockIssues']['parameterTypeMismatch']);
    }

    /**
     * @return void
     */
    public function testCorrectlyRecognizesDifferentQualificationsOfSameClassName(): void
    {
        $output = $this->lintFile('DocblockCorrectnessParamTypeDifferentQualifications.phpt');

        $this->assertEquals([

        ], $output['errors']['docblockIssues']['parameterTypeMismatch']);
    }

    /**
     * @return void
     */
    public function testCorrectlyIdentifiesDocblockSuperfluousParameters(): void
    {
        $output = $this->lintFile('DocblockCorrectnessSuperfluousParameters.phpt');

        $this->assertEquals([
            [
                'name'       => 'some_function_extra_parameter',
                'line'       => 20,
                'start'      => 270,
                'end'        => 271,
                'parameters' => ['$extra1', '$extra2']
            ]
        ], $output['errors']['docblockIssues']['superfluousParameter']);
    }

    /**
     * @return void
     */
    public function testCorrectlyIdentifiesDocblockMissingVarTag(): void
    {
        $output = $this->lintFile('DocblockCorrectnessMissingVarTag.phpt');

        $this->assertEquals([
            [
                'name'       => 'property',
                'line'       => 15,
                'start'      => 116,
                'end'        => 125
            ],

            [
                'name'       => 'CONSTANT',
                'line'       => 10,
                'start'      => 64,
                'end'        => 73
            ]
        ], $output['errors']['docblockIssues']['varTagMissing']);
    }

    /**
     * @return void
     */
    public function testCorrectlyIdentifiesDeprecatedCategoryTag(): void
    {
        $output = $this->lintFile('DocblockCorrectnessDeprecatedCategoryTag.phpt');

        $this->assertEquals([
            [
                'name'       => 'C',
                'line'       => 8,
                'start'      => 47,
                'end'        => 48
            ]
        ], $output['warnings']['docblockIssues']['deprecatedCategoryTag']);
    }

    /**
     * @return void
     */
    public function testCorrectlyIdentifiesDeprecatedSubpackageTag(): void
    {
        $output = $this->lintFile('DocblockCorrectnessDeprecatedSubpackageTag.phpt');

        $this->assertEquals([
            [
                'name'       => 'C',
                'line'       => 8,
                'start'      => 49,
                'end'        => 50
            ]
        ], $output['warnings']['docblockIssues']['deprecatedSubpackageTag']);
    }

    /**
     * @return void
     */
    public function testCorrectlyIdentifiesDeprecatedLinkTag(): void
    {
        $output = $this->lintFile('DocblockCorrectnessDeprecatedLinkTag.phpt');

        $this->assertEquals([
            [
                'name'       => 'C',
                'line'       => 8,
                'start'      => 63,
                'end'        => 64
            ]
        ], $output['warnings']['docblockIssues']['deprecatedLinkTag']);
    }
}
