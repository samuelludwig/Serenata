<?php

namespace Serenata\Tests\Integration\UserInterface\Command;

use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Linting\LintingSettings;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class LintCommandTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('lintCommand');

        $this->expectException(FileNotFoundStorageException::class);

        $command->lint('DoesNotExist.phpt', 'Code', new LintingSettings(
            false,
            false,
            false,
            false,
            false,
            false,
            false
        ));
    }
}
