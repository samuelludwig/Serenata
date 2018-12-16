<?php

namespace Serenata\Tests\Integration\Linting;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Linting\Diagnostic;
use Serenata\Linting\DiagnosticSeverity;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class LinterTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testIdentifiesSyntaxErrors(): void
    {
        $output = $this->lintFile('SyntaxError.phpt', true);

        static::assertEquals([
            new Diagnostic(
                new Range(
                    new Position(5, 15),
                    new Position(5, 15)
                ),
                DiagnosticSeverity::ERROR,
                null,
                'Syntax',
                'Syntax error, unexpected \';\' on line 6',
                null
            ),

            new Diagnostic(
                new Range(
                    new Position(7, 22),
                    new Position(7, 22)
                ),
                DiagnosticSeverity::ERROR,
                null,
                'Syntax',
                'Syntax error, unexpected \';\' on line 8',
                null
            ),
        ], $output);
    }

    /**
     * @param string $filePath
     * @param bool   $indexingMayFail
     *
     * @return array
     */
    private function lintFile(string $filePath, bool $indexingMayFail = false): array
    {
        $path = __DIR__ . '/LinterTest/' . $filePath;

        $this->indexTestFile($this->container, $path, $indexingMayFail);

        $linter = $this->container->get('linter');

        return $linter->lint(file_get_contents($path));
    }
}
