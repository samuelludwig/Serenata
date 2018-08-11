<?php

namespace Serenata\Tests\Integration\UserInterface\Command;

use Serenata\Common\Position;

use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class TooltipCommandTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('tooltipCommand');

        $this->expectException(FileNotFoundStorageException::class);

        $command->getTooltip('DoesNotExist.phpt', 'Code', new Position(0, 1));
    }
}
