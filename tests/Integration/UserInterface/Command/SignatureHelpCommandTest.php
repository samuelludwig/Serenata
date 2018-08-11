<?php

namespace Serenata\Tests\Integration\UserInterface\Command;

use Serenata\Common\Position;

use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class SignatureHelpCommandTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('signatureHelpCommand');

        $this->expectException(FileNotFoundStorageException::class);

        $command->signatureHelp('DoesNotExist.phpt', 'Code', new Position(0, 1));
    }
}
