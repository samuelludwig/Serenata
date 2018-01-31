<?php

namespace PhpIntegrator\Tests\Unit\Sockets;

use AssertionError;
use UnexpectedValueException;

use Psr\Container\ContainerInterface;

use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponse;
use PhpIntegrator\Sockets\JsonRpcErrorCode;
use PhpIntegrator\Sockets\JsonRpcQueueItem;
use PhpIntegrator\Sockets\JsonRpcQueueItemProcessor;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;

use PHPUnit\Framework\MockObject\MockObject;

use PhpIntegrator\UserInterface\Command\CommandInterface;

class JsonRpcQueueItemProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject
     */
    private $containerMock;

    /**
     * @var MockObject
     */
    private $jsonRpcResponseSenderMock;

    /**
     * @var MockObject
     */
    private $commandMock;

    /// @inherited
    public function setUp()
    {
        $this->containerMock = $this->getMockBuilder(ContainerInterface::class)
            ->setMethods(['get', 'has'])
            ->getMock();

        $this->jsonRpcResponseSenderMock = $this->getMockBuilder(JsonRpcResponseSenderInterface::class)
            ->setMethods(['send'])
            ->getMock();

        $this->commandMock = $this->getMockBuilder(CommandInterface::class)
            ->setMethods(['execute'])
            ->getMock();

        $this->containerMock->method('get')->with('testCommand')->willReturn($this->commandMock);
    }

    /**
     * @return void
     */
    public function testRelaysItemToAppropriateCommand(): void
    {
        $jsonRpcQueueItemProcessor = new JsonRpcQueueItemProcessor($this->containerMock);

        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', 'test'),
            $this->jsonRpcResponseSenderMock
        );

        $response = new JsonRpcResponse('theRequestId', 'result', null, '2.0');

        $this->commandMock->expects($this->once())->method('execute')->with($queueItem)->willReturn($response);

        $this->jsonRpcResponseSenderMock->expects($this->once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse) use ($response) {
                static::assertSame($response, $jsonRpcResponse);
            }
        );

        $jsonRpcQueueItemProcessor->process($queueItem);
    }

    /**
     * @return void
     */
    public function testSendsGenericRuntimeErrorWhenRuntimeExceptionOccursDuringCommandRequestHandling(): void
    {
        $jsonRpcQueueItemProcessor = new JsonRpcQueueItemProcessor($this->containerMock);

        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', 'test'),
            $this->jsonRpcResponseSenderMock
        );

        $this->commandMock->expects($this->once())->method('execute')->with($queueItem)->willThrowException(
            new UnexpectedValueException('Exception message')
        );

        $this->jsonRpcResponseSenderMock->expects($this->once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse) {
                static::assertSame('theRequestId', $jsonRpcResponse->getId());
                static::assertSame(JsonRpcErrorCode::GENERIC_RUNTIME_ERROR, $jsonRpcResponse->getError()->getCode());
                static::assertSame('Exception message', $jsonRpcResponse->getError()->getMessage());
                static::assertSame(null, $jsonRpcResponse->getError()->getData());
            }
        );

        $jsonRpcQueueItemProcessor->process($queueItem);
    }

    /**
     * @return void
     */
    public function testSendsFatalServerErrorWhenFatalExceptionOccursDuringCommandRequestHandling(): void
    {
        $jsonRpcQueueItemProcessor = new JsonRpcQueueItemProcessor($this->containerMock);

        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', 'test'),
            $this->jsonRpcResponseSenderMock
        );

        $this->commandMock->expects($this->once())->method('execute')->with($queueItem)->will($this->throwException(
            new AssertionError('Exception message')
        ));

        $this->jsonRpcResponseSenderMock->expects($this->once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse) {
                static::assertSame('theRequestId', $jsonRpcResponse->getId());
                static::assertSame(JsonRpcErrorCode::FATAL_SERVER_ERROR, $jsonRpcResponse->getError()->getCode());
                static::assertSame('Exception message', $jsonRpcResponse->getError()->getMessage());
                static::assertNotNull($jsonRpcResponse->getError()->getData());
            }
        );

        $jsonRpcQueueItemProcessor->process($queueItem);
    }

    /**
     * @return void
     */
    public function testSendsErrorWithCancelledCodeIfRequestWasCancelled(): void
    {
        $jsonRpcQueueItemProcessor = new JsonRpcQueueItemProcessor($this->containerMock);

        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', 'test'),
            $this->jsonRpcResponseSenderMock,
            true
        );

        $this->commandMock->expects($this->never())->method('execute');

        $this->jsonRpcResponseSenderMock->expects($this->once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse) {
                static::assertSame('theRequestId', $jsonRpcResponse->getId());
                static::assertSame(JsonRpcErrorCode::REQUEST_CANCELLED, $jsonRpcResponse->getError()->getCode());
                static::assertSame('Request was cancelled', $jsonRpcResponse->getError()->getMessage());
                static::assertSame(null, $jsonRpcResponse->getError()->getData());
            }
        );

        $jsonRpcQueueItemProcessor->process($queueItem);
    }
}
