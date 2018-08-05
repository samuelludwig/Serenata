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

        static::assertSame(2, count($output['errors']));
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
