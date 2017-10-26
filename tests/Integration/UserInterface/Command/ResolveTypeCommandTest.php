<?php

namespace PhpIntegrator\Tests\Integration\UserInterface\Command;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

use PhpIntegrator\Indexing\FileNotFoundStorageException;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

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

        static::assertSame('\C', $command->resolveType('C', $path, 1, UseStatementKind::TYPE_CLASSLIKE));
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('resolveTypeCommand');

        $this->expectException(FileNotFoundStorageException::class);

        $command->resolveType('A', 'DoesNotExist.phpt', 1, UseStatementKind::TYPE_CLASSLIKE);
    }
}
