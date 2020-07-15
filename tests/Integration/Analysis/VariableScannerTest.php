<?php

namespace Serenata\Tests\Integration\Analysis;

use Serenata\Common\Position;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

final class VariableScannerTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testDoesNotReturnVariableStartingAtOffset(): void
    {
        $output = $this->getAvailableVariables('VariableStartingAtOffset.phpt');

        self::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testDoesNotReturnVariableEndingAtOffset(): void
    {
        $output = $this->getAvailableVariables('VariableEndingAtOffset.phpt');

        self::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testDoesNotReturnVariablePartOfAssignmentStartingAtOffset(): void
    {
        $output = $this->getAvailableVariables('VariablePartOfAssignmentStartingAtOffset.phpt');

        self::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testDoesNotReturnVariablePartOfAssignmentEndingAtOffset(): void
    {
        $output = $this->getAvailableVariables('VariablePartOfAssignmentEndingAtOffset.phpt');

        self::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testDoesNotReturnVariableOperatingAsLeftOperandOfAssignmentAtOffset(): void
    {
        $output = $this->getAvailableVariables('VariableOperatingAsLeftOperandOfAssignmentAtOffset.phpt');

        self::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testReturnsOnlyVariablesRelevantToTheGlobalScope(): void
    {
        $output = $this->getAvailableVariables('GlobalScope.phpt');

        self::assertSame([
            '$var3' => ['name' => '$var3', 'type' => null],
            '$var2' => ['name' => '$var2', 'type' => null],
            '$var1' => ['name' => '$var1', 'type' => null],
        ], $output);
    }

    /**
     * @return void
     */
    public function testReturnsOnlyVariablesRelevantToTheCurrentFunction(): void
    {
        $output = $this->getAvailableVariables('FunctionScope.phpt');

        self::assertSame([
            '$closure' => ['name' => '$closure', 'type' => null],
            '$param2'  => ['name' => '$param2',  'type' => null],
            '$param1'  => ['name' => '$param1',  'type' => null],
        ], $output);
    }

    /**
     * @return void
     */
    public function testReturnsOnlyVariablesRelevantToTheCurrentMethod(): void
    {
        $output = $this->getAvailableVariables('ClassMethodScope.phpt');

        self::assertSame([
            '$this'    => ['name' => '$this',    'type' => null],
            '$closure' => ['name' => '$closure', 'type' => null],
            '$param2'  => ['name' => '$param2',  'type' => null],
            '$param1'  => ['name' => '$param1',  'type' => null],
        ], $output);
    }

    /**
     * @return void
     */
    public function testReturnsOnlyVariablesRelevantToTheCurrentClosure(): void
    {
        $output = $this->getAvailableVariables('ClosureScope.phpt');

        self::assertSame([
            '$this'         => ['name' => '$this',         'type' => null],
            '$test'         => ['name' => '$test',         'type' => null],
            '$something'    => ['name' => '$something',    'type' => null],
            '$closureParam' => ['name' => '$closureParam', 'type' => null],
        ], $output);
    }

    /**
     * @return void
     */
    public function testReturnsOnlyVariablesRelevantToTheOutsideScopeInClosureBinding(): void
    {
        $output = $this->getAvailableVariables('ClosureBindingScope.phpt');

        self::assertSame([
            // TODO: This should also be available as you can bind the variable that the closure is assigned to inside
            // said closure.
            // '$closure' => ['name' => '$closure', 'type' => null],
            '$param2'  => ['name' => '$param2',  'type' => null],
            '$param1'  => ['name' => '$param1',  'type' => null],
        ], $output);
    }

    /**
     * @return void
     */
    public function testReturnsOnlyVariablesRelevantToTheOutsideScopeInClosureBindingWithPrefix(): void
    {
        $output = $this->getAvailableVariables('ClosureBindingScopeWithPrefix.phpt');

        self::assertSame([
            '$var1'  => ['name' => '$var1', 'type' => null],
        ], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyIgnoresVariousStatements(): void
    {
        $file = 'VariousStatements.phpt';
        $fullPath = $this->getPathFor($file);

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $fullPath);
        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($fullPath);

        $scanner = $container->get('variableScanner');

        $i = 1;
        $markerOffsets = [];

        while (true) {
            $markerOffset = $this->getMarkerOffset($code, "// MARKER_{$i}");

            if ($markerOffset === null) {
                break;
            }

            $markerOffsets[$i++] = $markerOffset;
        }

        $doMarkerTest = function (
            $markerNumber,
            array $variableNames
        ) use (
            $scanner,
            $fullPath,
            $markerOffsets,
            $code
        ): void {
            $list = [];

            foreach ($variableNames as $variableName) {
                $list[$variableName] = ['name' => $variableName, 'type' => null];
            }

            self::assertSame(
                $list,
                $scanner->getAvailableVariables(
                    new TextDocumentItem($fullPath, file_get_contents($fullPath)),
                    Position::createFromByteOffset($markerOffsets[$markerNumber], $code, PositionEncoding::VALUE)
                )
            );
        };

        $doMarkerTest(1, []);
        $doMarkerTest(2, ['$a']);
        $doMarkerTest(3, []);
        $doMarkerTest(4, ['$b']);
        $doMarkerTest(5, []);
        $doMarkerTest(6, ['$b2']);
        $doMarkerTest(7, []);
        $doMarkerTest(8, ['$c']);
        $doMarkerTest(9, []);
        $doMarkerTest(10, ['$d']);
        $doMarkerTest(11, ['$value', '$key']);
        $doMarkerTest(12, ['$e', '$value', '$key']);
        $doMarkerTest(13, ['$i']);
        $doMarkerTest(14, ['$f', '$i']);
        $doMarkerTest(15, []);
        $doMarkerTest(16, ['$g']);
        $doMarkerTest(17, []);
        $doMarkerTest(18, ['$h']);
        $doMarkerTest(19, []);
        $doMarkerTest(20, ['$i']);
        $doMarkerTest(21, []);
        $doMarkerTest(22, ['$j']);
        $doMarkerTest(23, []);
        $doMarkerTest(24, ['$k']);
        $doMarkerTest(25, ['$e']);
        $doMarkerTest(26, ['$l', '$e']);
        $doMarkerTest(27, ['$e']);
        $doMarkerTest(28, ['$m', '$e']);
        // $doMarkerTest(29, []); // TODO: Can't be solved for now, see also the implementation code.
        $doMarkerTest(30, ['$n']);
    }

    /**
     * @return void
     */
    public function testIgnoresErrorsInParameterNames(): void
    {
        self::assertSame([], $this->getAvailableVariables('ErrorInParameterName.phpt'));
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getPathFor(string $name): string
    {
        return 'file:///' . __DIR__ . '/VariableScannerTest/' . $name;
    }

    /**
     * @param string $file
     * @param bool   $mayIndexingFail
     *
     * @return array
     */
    private function getAvailableVariables(string $file, bool $mayIndexingFail = false): array
    {
        $path = $this->getPathFor($file);

        $container = $this->createTestContainer();

        $code = $container->get('sourceCodeStreamReader')->getSourceCodeFromFile($path);

        $markerString = '// <MARKER>';

        $markerOffset = $this->getMarkerOffset($code, $markerString);

        // Strip marker so it does not influence further processing.
        $code = str_replace($markerString, '', $code);

        $this->indexTestFileWithSource($container, $path, $code);

        return $container->get('variableScanner')->getAvailableVariables(
            new TextDocumentItem($file, $code),
            Position::createFromByteOffset($markerOffset, $code, PositionEncoding::VALUE)
        );
    }

    /**
     * @param string $code
     * @param string $marker
     *
     * @return int|null
     */
    private function getMarkerOffset(string $code, string $marker): ?int
    {
        $markerOffset = mb_strpos($code, $marker);

        return $markerOffset !== false ? $markerOffset : null;
    }
}
