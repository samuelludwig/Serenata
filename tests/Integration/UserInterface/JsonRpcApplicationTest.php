<?php

namespace Serenata\Tests\Integration\UserInterface;

use React;

use PHPUnit\Framework\TestCase;

// use Serenata\Sockets\JsonRpcRequest;
// use Serenata\Sockets\JsonRpcConnectionHandler;
// use Serenata\Sockets\JsonRpcRequestHandlerInterface;

use Symfony\Component\Process\Exception\ProcessTimedOutException;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

final class JsonRpcApplicationTest extends TestCase
{
    /**
     * @var string
     */
    public const TCP_TEST_URI = 'tcp://127.0.0.1:12345';

    /**
     * @return void
     */
    public function testSocketServerStartsCorrectly(): void
    {
        $process = $this->spawnTestInstance();
        $process->setTimeout(5);
        $process->start();

        $this->waitForServerStart($process);

        static::assertTrue($process->isRunning());

        $process->stop();

        static::assertFalse($process->isRunning());
    }

    /**
     * @return void
     */
    public function testSocketServerAcceptsTcpClients(): void
    {
        $process = $this->spawnTestInstance();
        $process->setTimeout(5);
        $process->start();

        $this->waitForServerStart($process);

        $eventLoop = React\EventLoop\Factory::create();
        $connector = new React\Socket\Connector($eventLoop, [
            'timeout' => 5,
        ]);

        $didConnect = false;

        $connector
            ->connect(self::TCP_TEST_URI)
            ->then(
                function (React\Socket\ConnectionInterface $connection) use (&$didConnect, $eventLoop): void {
                    $didConnect = true;

                    $eventLoop->stop();
                },
                function (): void {
                    static::fail('Failed connecting to TCP server instance using client for an unknown reason');
                }
            );

        $eventLoop->addTimer(5, function () use ($eventLoop): void {
            $eventLoop->stop();
        });

        $eventLoop->run();

        $process->stop();

        static::assertTrue($didConnect, 'Timed out trying to connect to TCP server instance using client');
    }

    // /**
    //  * @return void
    //  */
    // public function testSendingJsonRpcRequestAndReceivingResponseWorks(): void
    // {
    //     $process = $this->spawnTestInstance();
    //     $process->setTimeout(5);
    //     $process->start();

    //     $this->waitForServerStart($process);

    //     $eventLoop = React\EventLoop\Factory::create();
    //     $connector = new React\Socket\Connector($eventLoop, [
    //         'timeout' => 5,
    //     ]);

    //     $jsonRpcRequestHandlerMock = self::getMockBuilder(JsonRpcRequestHandlerInterface::class)->getMock();
    //     $jsonRpcRequestHandlerMock->expects(self::once())->method('handle')->willReturn(new JsonRpcRequest(null, 'serenata/test/hello', [
    //         'greeting' => 'hi!',
    //     ]));

    //     $connector
    //         ->connect(self::TCP_TEST_URI)
    //         ->then(
    //             function (React\Socket\ConnectionInterface $connection) use (
    //                 &$didConnect,
    //                 $jsonRpcRequestHandlerMock
    //             ): void {
    //                 // Also used by the server to process requests, but no reason we can't also use it for the client.
    //                 $clientConnectionHandler = new JsonRpcConnectionHandler($connection, $jsonRpcRequestHandlerMock);

    //                 $request = new JsonRpcRequest(null, 'serenata/internal/echoMessage', [
    //                     'message' => new JsonRpcRequest(null, 'serenata/test/hello', [
    //                         'greeting' => 'hi!',
    //                     ]),
    //                 ]);

    //                 $clientConnectionHandler->send($request);
    //             },
    //             function (): void {
    //                 static::fail('Failed connecting to TCP server instance using client for an unknown reason');
    //             }
    //         );

    //     $eventLoop->addTimer(5, function () use ($eventLoop): void {


    //         // TODO: Need to wait here for request to actually propagate to server, then give server chance
    //         // to actually handle it and send back info over socket. Need to wait for main loop.
    //         // sleep(2);

    //         // $connection->end();
    //         // $eventLoop->stop();


    //         $eventLoop->stop();
    //     });

    //     $eventLoop->run();

    //     $process->stop();
    // }

    /**
     * @param Process $process
     */
    private function waitForServerStart(Process $process): void
    {
        try {
            $process->waitUntil(function ($type, $output): bool {
                return
                    $type === Process::OUT &&
                    $output === "Starting server bound to socket on URI " . self::TCP_TEST_URI . "...\n";
            });
        } catch (ProcessTimedOutException $e) {
            static::fail(
                'Timed out waiting for server start message. Either the server is not starting correctly or it is no ' .
                'longer displaying an informational message that it started'
            );
        }
    }

    /**
     * @param string $uri
     *
     * @return Process
     */
    private function spawnTestInstance(string $uri = self::TCP_TEST_URI): Process
    {
        $phpExecutableFinder = new PhpExecutableFinder();
        $phpExecutablePath = $phpExecutableFinder->find(false);

        static::assertNotFalse($phpExecutablePath, 'Cannot find path to PHP path to spawn testserver with');

        return new Process(array_merge(
            [$phpExecutablePath],
            $phpExecutableFinder->findArguments(),
            [
                __DIR__ . '/../../../bin/console',
                '--uri=' . $uri,
            ]
        ));
    }
}
