<?php

namespace Serenata\Tests\Unit\Sockets;

use LogicException;
use UnexpectedValueException;

use PHPUnit\Framework\MockObject\MockObject;

use PHPUnit\Framework\TestCase;

use Serenata\Sockets\JsonRpcRequest;
use Serenata\Sockets\JsonRpcResponse;
use Serenata\Sockets\JsonRpcErrorCode;
use Serenata\Sockets\JsonRpcQueueItem;
use Serenata\Sockets\JsonRpcQueueItemProcessor;
use Serenata\Sockets\JsonRpcMessageSenderInterface;

use Serenata\UserInterface\JsonRpcQueueItemHandler\JsonRpcQueueItemHandlerInterface;

use Serenata\UserInterface\JsonRpcQueueItemHandlerFactoryInterface;

use Serenata\Utility\StreamInterface;

use Serenata\Workspace\Configuration\WorkspaceConfiguration;

use Serenata\Workspace\Workspace;
use Serenata\Workspace\ActiveWorkspaceManager;

class JsonRpcQueueItemProcessorTest extends TestCase
{
    /**
     * @var MockObject&JsonRpcQueueItemHandlerFactoryInterface
     */
    private $jsonRpcQueueItemHandlerFactoryMock;

    /**
     * @var MockObject&StreamInterface
     */
    private $streamMock;

    /**
     * @var MockObject&JsonRpcMessageSenderInterface
     */
    private $jsonRpcResponseSenderMock;

    /**
     * @var string
     */
    private $testMethod;

    /**
     * @var MockObject
     */
    private $commandMock;

    /**
     * @var MockObject
     */
    private $activeWorkspaceManagerMock;

    /**
     * @var JsonRpcQueueItemProcessor
     */
    private $jsonRpcQueueItemProcessor;

    /// @inherited
    public function setUp()
    {
        $this->jsonRpcQueueItemHandlerFactoryMock = $this
            ->getMockBuilder(JsonRpcQueueItemHandlerFactoryInterface::class)
            ->getMock();

        $this->streamMock = $this->getMockBuilder(StreamInterface::class)
            ->getMock();

        $this->jsonRpcResponseSenderMock = $this->getMockBuilder(JsonRpcMessageSenderInterface::class)
            ->setMethods(['send'])
            ->getMock();

        $this->commandMock = $this->getMockBuilder(JsonRpcQueueItemHandlerInterface::class)
            ->setMethods(['execute'])
            ->getMock();

        $this->activeWorkspaceManagerMock = $this->getMockBuilder(ActiveWorkspaceManager::class)
            ->setMethods(['getActiveWorkspace'])
            ->getMock();

        $this->activeWorkspaceManagerMock->method('getActiveWorkspace')->willReturn(new Workspace(
            new WorkspaceConfiguration('test-id', [], ':memory:', 7.1, [], [])
        ));

        $this->testMethod = '$/cancelRequest';
        $this->jsonRpcQueueItemHandlerFactoryMock->method('create')->with('$/cancelRequest')->willReturn(
            $this->commandMock
        );

        $this->jsonRpcQueueItemProcessor = new JsonRpcQueueItemProcessor(
            $this->jsonRpcQueueItemHandlerFactoryMock,
            $this->streamMock,
            $this->activeWorkspaceManagerMock
        );
    }

    /**
     * @return void
     */
    public function testRelaysItemToAppropriateCommand(): void
    {
        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', $this->testMethod),
            $this->jsonRpcResponseSenderMock
        );

        $response = new JsonRpcResponse('theRequestId', 'result', null, '2.0');

        $this->commandMock->expects($this->once())->method('execute')->with($queueItem)->willReturn($response);

        $this->jsonRpcResponseSenderMock->expects($this->once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse) use ($response) {
                static::assertSame($response, $jsonRpcResponse);
            }
        );

        $this->jsonRpcQueueItemProcessor->process($queueItem);
    }

    /**
     * @return void
     */
    public function testSendsGenericRuntimeErrorWhenRuntimeExceptionOccursDuringCommandRequestHandling(): void
    {
        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', $this->testMethod),
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

        $this->jsonRpcQueueItemProcessor->process($queueItem);
    }

    /**
     * @return void
     */
    public function testSendsFatalServerErrorWhenFatalExceptionOccursDuringCommandRequestHandling(): void
    {
        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', $this->testMethod),
            $this->jsonRpcResponseSenderMock
        );

        $this->commandMock->expects($this->once())->method('execute')->with($queueItem)->will($this->throwException(
            new LogicException('Exception message')
        ));

        $this->jsonRpcResponseSenderMock->expects($this->once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse) {
                static::assertSame('theRequestId', $jsonRpcResponse->getId());
                static::assertSame(JsonRpcErrorCode::FATAL_SERVER_ERROR, $jsonRpcResponse->getError()->getCode());
                static::assertSame('Exception message', $jsonRpcResponse->getError()->getMessage());
                static::assertNotNull($jsonRpcResponse->getError()->getData());
            }
        );

        $this->jsonRpcQueueItemProcessor->process($queueItem);
    }

    /**
     * @return void
     */
    public function testSendsErrorWithCancelledCodeIfRequestWasCancelled(): void
    {
        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', $this->testMethod),
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

        $this->jsonRpcQueueItemProcessor->process($queueItem);
    }
}
