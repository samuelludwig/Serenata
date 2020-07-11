<?php

namespace Serenata\Tests\Integration\Refactoring;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Refactoring\UseStatementUnnecessaryException;
use Serenata\Refactoring\UseStatementAlreadyExistsException;
use Serenata\Refactoring\UseStatementEqualsNamespaceException;
use Serenata\Refactoring\NonCompoundNameInAnonymousNamespaceException;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Utility\TextEdit;
use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

final class UseStatementInsertionCreatorTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testInsertsBeforeFirstNodeIfNoUseStatementsNorAnyNamespaceExists(): void
    {
        $name = '\Foo\Bar';
        $insertionPoint = new Position(2, 0);
        $file = 'NoNamespaceAndNoUseStatements.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testInsertsAfterNamespaceIfNoUseStatementsExist(): void
    {
        $name = '\Foo\Bar';
        $insertionPoint = new Position(4, 0);
        $file = 'NamespaceWithNoUseStatements.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testSortsNewImportHigherWhenItsFirstSegmentIsAlphabeticallyFirst(): void
    {
        $name = 'Aab\Cdd';
        $insertionPoint = new Position(2, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testSortsNewImportLowerWhenItsFirstSegmentIsAlphabeticallyLast(): void
    {
        $name = 'Foo\Bar';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testSortsNewImportLowerWhenItHasMoreNamespaceSegmentsThanTheOther(): void
    {
        $name = 'Foo\Bar\Baz\Qux';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testSortsNewImportHigherWhenItHasSimilarNamespaceSegmentsAsTheOtherAndIsTheSameLengthAndIsAlphabeticallyMoreRelevant(): void
    {
        $name = 'Bar\Baz\Aux';
        $insertionPoint = new Position(2, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testSortsNewImportLowerWhenItHasSimilarNamespaceSegmentsAsTheOtherAndIsTheSameLengthAndIsAlphabeticallyLessRelevant(): void
    {
        $name = 'Bar\Baz\Zux';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testSortsNewImportHigherWhenItHasSimilarNamespaceSegmentsAsTheOtherAndIsNotTheSameLengthAndIsShorter(): void
    {
        $name = 'Bar\Baz\Qu';
        $insertionPoint = new Position(2, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testAlwaysSortsUnqualifiedImportsBeforeQualifiedImports(): void
    {
        $name = 'Cabcdefghijklmnopqrstuvwxyz';
        $insertionPoint = new Position(4, 0);
        $file = 'QualifiedImportWithUnqualifiedImport.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testSortsNewImportLowerWhenItHasSimilarNamespaceSegmentsAsTheOtherAndIsNotTheSameLengthAndIsLonger(): void
    {
        $name = 'Bar\Baz\Quux';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testSortsNewImportThatContainsExistingImportLower(): void
    {
        $name = 'Bar\Baz\Qux\Another';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "\nuse {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, true)
        );
    }

    /**
     * @return void
     */
    public function testGroupsUseStatementsWithSimilarNamespaceSegmentsTogetherByAttachingToTopOfGroup(): void
    {
        $name = 'Three\Segments\Bar';
        $insertionPoint = new Position(8, 0);
        $file = 'ExistingUseStatementWithSurroundingDifferentlySegmentedOnes.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, true)
        );
    }

    /**
     * @return void
     */
    public function testGroupsUseStatementsWithSimilarNamespaceSegmentsTogetherByAttachingToBottomOfGroup(): void
    {
        $name = 'Three\Segments\Qux';
        $insertionPoint = new Position(9, 0);
        $file = 'ExistingUseStatementWithSurroundingDifferentlySegmentedOnes.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, true)
        );
    }

    /**
     * @return void
     */
    public function testAddsAdditionalNewlineIfImportHasDifferentPrefixThanExistingImportsAndIsAllowed(): void
    {
        $name = '\Foo\Bar\Baz';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "\nuse {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, true)
        );
    }

    /**
     * @return void
     */
    public function testDoesNotAddAdditionalNewlineIfImportHasSamePrefixAsExistingImportEvenIfAllowed(): void
    {
        $name = '\Bar\Baz\Quux';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, true)
        );
    }

    /**
     * @return void
     */
    public function testSkipsAdditionalNewlineIfImportHasDifferentPrefixThanExistingImportsAndIsNotAllowed(): void
    {
        $name = '\Foo\Bar\Baz';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingUseStatement.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testRandomSkipsAdditionalNewlineIfImportHasSamePrefixAsExistingImportsIfShouldBePlacedBetweenThemAndAllowed(): void
    {
        $name = '\Foo\Bar\Caz';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingUseStatementsWithSamePrefix.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, true)
        );
    }

    /**
     * @return void
     */
    public function testInsertsAdditionalNewlineInOrderToMaintainSingleNewlineBetweenFirstUseStatementAndExistingClass(): void
    {
        $name = '\Foo\Bar\Caz';
        $insertionPoint = new Position(4, 0);
        $file = 'NoUseStatementsAndClass.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, true)
        );
    }

    /**
     * @return void
     */
    public function testSelectsActiveNamespaceBlockBasedOnPosition(): void
    {
        $name = '\Foo\Bar';
        $insertionPoint = new Position(8, 0);
        $file = 'MultipleNamespaces.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testProperlyCalculatesFallbackLineInAnonymousNamespace(): void
    {
        $name = 'Foo\Bar\Baz';
        $insertionPoint = new Position(3, 0);
        $file = 'AnonymousNamespace.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testInsertsFunctionUseStatementForFunctions(): void
    {
        $name = '\Foo\Bar\func';
        $insertionPoint = new Position(2, 0);
        $file = 'NoNamespaceAndNoUseStatements.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use function {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_FUNCTION, false)
        );
    }

    /**
     * @return void
     */
    public function testInsertsConstantUseStatementForConstants(): void
    {
        $name = '\Foo\Bar\CONSTANT';
        $insertionPoint = new Position(2, 0);
        $file = 'NoNamespaceAndNoUseStatements.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use const {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CONSTANT, false)
        );
    }

    /**
     * @return void
     */
    public function testInsertsFunctionImportAfterClasslikeImportsAndInSeparateGroup(): void
    {
        $name = '\Foo\a';
        $insertionPoint = new Position(3, 0);
        $file = 'ClasslikeUseStatementAlreadyExists.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "\nuse function {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_FUNCTION, true)
        );
    }

    /**
     * @return void
     */
    public function testInsertsConstantImportAfterClasslikeImportsAndInSeparateGroup(): void
    {
        $name = '\Foo\A';
        $insertionPoint = new Position(3, 0);
        $file = 'ClasslikeUseStatementAlreadyExists.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "\nuse const {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CONSTANT, true)
        );
    }

    /**
     * @return void
     */
    public function testInsertsClasslikeImportAfterFunctionImportsAndInSeparateGroup(): void
    {
        $name = '\B\Foo';
        $insertionPoint = new Position(3, 0);
        $file = 'MultipleFunctionUseStatementsInNamespace.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "\nuse {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, true)
        );
    }

    /**
     * @return void
     */
    public function testInsertsFunctionImportsBeforeConstantImportsandInSeparateGroup(): void
    {
        $name = '\Foo\Bar\a';
        $insertionPoint = new Position(3, 0);
        $file = 'FunctionUseStatementAlreadyExists.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "\nuse const {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CONSTANT, true)
        );
    }

    /**
     * @return void
     */
    public function testInsertsFunctionImportsBeforeConstantImportsandInExistingGroupIfGroupsAlreadyExist(): void
    {
        $name = '\Foo\Bar\qux';
        $insertionPoint = new Position(3, 0);
        $file = 'ExistingMixedUseStatements.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use function {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_FUNCTION, true)
        );
    }

    /**
     * @return void
     */
    public function testInsertsClasslikeImportIfImportForFunctionAlreadyExists(): void
    {
        $name = '\Foo\Bar\FOO';
        $insertionPoint = new Position(1, 0);
        $file = 'ExistingMixedUseStatements.phpt';

        self::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "\nuse {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, true)
        );
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenClasslikeUseStatementAlreadyExists(): void
    {
        $name = '\Foo\Bar';
        $file = 'ClasslikeUseStatementAlreadyExists.phpt';

        $this->expectException(UseStatementAlreadyExistsException::class);

        $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false);
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenClasslikeUseStatementAlreadyExistsInCompoundStatement(): void
    {
        $name = '\Foo\Bar';
        $file = 'ClasslikeUseStatementAlreadyExistsInGroupedStatement.phpt';

        $this->expectException(UseStatementAlreadyExistsException::class);

        $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false);
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenAddingNonCompoundClasslikeUseStatementInImplicitAnonymousNamespace(): void
    {
        $name = 'Foo';
        $file = 'AnonymousNamespace.phpt';

        $this->expectException(NonCompoundNameInAnonymousNamespaceException::class);

        $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false);
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenAddingNonCompoundClasslikeUseStatementInExplicitAnonymousNamespace(): void
    {
        $name = 'Foo';
        $file = 'NoNamespaceAndNoUseStatements.phpt';

        $this->expectException(NonCompoundNameInAnonymousNamespaceException::class);

        $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false);
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenAddingUseStatementWithNameOfNamespace(): void
    {
        $name = 'A';
        $file = 'NamespaceWithNoUseStatements.phpt';

        $this->expectException(UseStatementEqualsNamespaceException::class);

        $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false);
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenAddingUseStatementForClassInSameNamespaceAsActiveNamespace(): void
    {
        $name = 'A\B';
        $file = 'NamespaceWithNoUseStatements.phpt';

        $this->expectException(UseStatementUnnecessaryException::class);

        $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false);
    }

    /**
     * @param string $file
     * @param string $name
     * @param string $kind
     * @param bool   $allowAdditionalNewlines
     *
     * @return TextEdit
     */
    private function create(
        string $file,
        string $name,
        string $kind,
        bool $allowAdditionalNewlines
    ): TextEdit {
        $path = $this->getPathFor($file);

        $container = $this->createTestContainer();

        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        $markerString = '// <MARKER>';

        $markerOffset = $this->getMarkerOffset($code, $markerString);

        // Strip marker so it does not influence further processing.
        $code = str_replace($markerString, '', $code);

        $this->indexTestFileWithSource($container, $path, $code);

        return $container->get('useStatementInsertionCreator')->create(
            $name,
            $kind,
            new TextDocumentItem($path, $code),
            Position::createFromByteOffset($markerOffset, $code, PositionEncoding::VALUE),
            $allowAdditionalNewlines
        );
    }

    /**
     * @param string $code
     * @param string $marker
     *
     * @return int
     */
    private function getMarkerOffset(string $code, string $marker): int
    {
        $markerOffset = mb_strpos($code, $marker);

        if ($markerOffset === false) {
            self::fail('No marker "' . $marker . '" found in test code');
        }

        return $markerOffset;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getPathFor(string $fileName): string
    {
        return 'file:///' . __DIR__ . '/UseStatementInsertionCreatorTest/' . $fileName;
    }
}
