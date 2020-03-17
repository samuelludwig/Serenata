<?php

namespace Serenata\Tests\Integration\Commands;

use Serenata\Commands\ClientCommandName;
use Serenata\Commands\OpenTextDocumentCommand;

use Serenata\Common\Position;

use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcMessageInterface;

use Serenata\Tests\Integration\AbstractIntegrationTest;

/**
 * @group Integration
 */
final class OpenTextDocumentCommandExecutorTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testReturnsMessageThatRequestClientToOpenTextDocument(): void
    {
        $uri = $this->getPathFor('Class.phpt');

        static::assertEquals(
            new JsonRpcRequest(null, ClientCommandName::OPEN_TEXT_DOCUMENT, [
                'uri' => $uri,
                'position' => new Position(6, 20),
            ]),
            $this->executeCommand($uri, 6, 20)
        );
    }



    private function executeCommand(string $file, int $line, int $character): ?JsonRpcMessageInterface
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('commandFactory')->create(OpenTextDocumentCommand::getCommandName(), [
            'uri' => $file,
            'position' => [
                'line'      => $line,
                'character' => $character,
            ],
        ]);

        return $this->container->get('commandExecutorFactory')->create($command)->execute($command);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/OpenTextDocumentCommandExecutorTest/' . $file;
    }
}
