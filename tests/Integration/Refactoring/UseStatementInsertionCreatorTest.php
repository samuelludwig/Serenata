<?php

namespace PhpIntegrator\Tests\Integration\Refactoring;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

use PhpIntegrator\Common\Range;
use PhpIntegrator\Common\Position;

use PhpIntegrator\Refactoring\UseStatementAlreadyExistsException;
use PhpIntegrator\Refactoring\NonCompoundNameInAnonymousNamespaceException;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

use PhpIntegrator\Utility\TextEdit;

class UseStatementInsertionCreatorTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testInsertsBeforeFirstNodeIfNoUseStatementsNorAnyNamespaceExists(): void
    {
        $name = '\Foo';
        $insertionPoint = new Position(2, 0);
        $file = 'NoNamespaceAndNoUseStatements.phpt';

        static::assertEquals(
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

        static::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
        );
    }

    /**
     * @return void
     */
    public function testSortsNewImportHigherWhenItHasFewerNamespaceSegmentsThanTheOther(): void
    {
        $name = 'Foo\Bar';
        $insertionPoint = new Position(2, 0);
        $file = 'ExistingUseStatement.phpt';

        static::assertEquals(
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

        static::assertEquals(
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

        static::assertEquals(
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

        static::assertEquals(
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

        static::assertEquals(
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

        static::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
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

        static::assertEquals(
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

        static::assertEquals(
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

        static::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
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

        static::assertEquals(
            new TextEdit(new Range($insertionPoint, $insertionPoint), "use {$name};\n"),
            $this->create($file, $name, UseStatementKind::TYPE_CLASSLIKE, false)
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
            $code,
            $markerOffset,
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
            static::fail('No marker "' . $marker . '" found in test code');
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
        return __DIR__ . '/UseStatementInsertionCreatorTest/' . $fileName;
    }
}
