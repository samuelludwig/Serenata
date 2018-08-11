<?php

namespace Serenata\Tests\Integration\UserInterface\Command;

use Serenata\Analysis\Visiting\UseStatementKind;

use Serenata\Common\Position;

use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class ResolveTypeCommandTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testCorrectlyResolvesSimpleType(): void
    {
        $path = __DIR__ . '/ResolveTypeCommandTest/' . 'ResolveType.phpt';

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('resolveTypeCommand');

        static::assertSame(
            '\C',
            $command->resolveType('C', $path, new Position(0, 1), UseStatementKind::TYPE_CLASSLIKE)
        );
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('resolveTypeCommand');

        $this->expectException(FileNotFoundStorageException::class);

        $command->resolveType('A', 'DoesNotExist.phpt', new Position(0, 1), UseStatementKind::TYPE_CLASSLIKE);
    }
}
