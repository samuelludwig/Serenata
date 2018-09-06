<?php

namespace Serenata\Tests\Integration\UserInterface\Command;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class LinterTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testIdentifiesSyntaxErrors(): void
    {
        $output = $this->lintFile('SyntaxError.phpt', true);

        static::assertSame([
            [
                'message' => 'Syntax error, unexpected \';\' on line 6',
                'start'   => 47,
                'end'     => 47,
            ],

            [
                'message' => 'Syntax error, unexpected \';\' on line 8',
                'start'   => 89,
                'end'     => 89,
            ],
        ], $output['errors']);
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
