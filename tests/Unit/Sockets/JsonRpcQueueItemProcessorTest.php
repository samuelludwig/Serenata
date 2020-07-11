<?php

namespace Serenata\Tests\Unit\Sockets;

use LogicException;
use UnexpectedValueException;

use React\Promise\Deferred;

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

final class JsonRpcQueueItemProcessorTest extends TestCase
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
     * @var MockObject&JsonRpcQueueItemHandlerInterface
     */
    private $commandMock;

    /**
     * @var MockObject&ActiveWorkspaceManager
     */
    private $activeWorkspaceManagerMock;

    /**
     * @var JsonRpcQueueItemProcessor
     */
    private $jsonRpcQueueItemProcessor;

    /**
     * @inheritDoc
     */
    public function setUp(): void
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
            new WorkspaceConfiguration([], ':memory:', 7.1, [], [])
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

        $responseDeferred = new Deferred();
        $responseDeferred->resolve($response);

        $this->commandMock->expects(self::once())->method('execute')->with($queueItem)->willReturn(
            $responseDeferred->promise()
        );

        $this->jsonRpcResponseSenderMock->expects(self::once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse) use ($response): void {
                self::assertSame($response, $jsonRpcResponse);
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

        $this->commandMock->expects(self::once())->method('execute')->with($queueItem)->willThrowException(
            new UnexpectedValueException('Exception message')
        );

        $this->jsonRpcResponseSenderMock->expects(self::once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse): void {
                self::assertSame('theRequestId', $jsonRpcResponse->getId());
                self::assertNotNull($jsonRpcResponse->getError());
                self::assertSame(JsonRpcErrorCode::GENERIC_RUNTIME_ERROR, $jsonRpcResponse->getError()->getCode());
                self::assertSame('Exception message', $jsonRpcResponse->getError()->getMessage());

                $data =  $jsonRpcResponse->getError()->getData();

                self::assertNotNull($data);
                self::assertNotNull($data['line']);
                self::assertNotNull($data['file']);
                self::assertNotNull($data['backtrace']);
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

        $this->commandMock->expects(self::once())->method('execute')->with($queueItem)->will(self::throwException(
            new LogicException('Exception message')
        ));

        $this->jsonRpcResponseSenderMock->expects(self::once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse): void {
                self::assertSame('theRequestId', $jsonRpcResponse->getId());
                self::assertNotNull($jsonRpcResponse->getError());
                self::assertSame(JsonRpcErrorCode::FATAL_SERVER_ERROR, $jsonRpcResponse->getError()->getCode());
                self::assertSame('Exception message', $jsonRpcResponse->getError()->getMessage());
                self::assertNotNull($jsonRpcResponse->getError()->getData());
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

        $this->commandMock->expects(self::never())->method('execute');

        $this->jsonRpcResponseSenderMock->expects(self::once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse): void {
                self::assertSame('theRequestId', $jsonRpcResponse->getId());
                self::assertNotNull($jsonRpcResponse->getError());
                self::assertSame(JsonRpcErrorCode::REQUEST_CANCELLED, $jsonRpcResponse->getError()->getCode());
                self::assertSame('Request was cancelled', $jsonRpcResponse->getError()->getMessage());
                self::assertSame(null, $jsonRpcResponse->getError()->getData());
            }
        );

        $this->jsonRpcQueueItemProcessor->process($queueItem);
    }

    /**
     * @return void
     */
    public function testSendsHandlesPromiseErrorsWhenExceptionOccursDuringCommandRequestHandling(): void
    {
        $queueItem = new JsonRpcQueueItem(
            new JsonRpcRequest('theRequestId', $this->testMethod),
            $this->jsonRpcResponseSenderMock
        );

        $responseDeferred = new Deferred();
        $responseDeferred->reject(new UnexpectedValueException('Exception message'));

        $this->commandMock->expects(self::once())->method('execute')->with($queueItem)->willReturn(
            $responseDeferred->promise()
        );

        $this->jsonRpcResponseSenderMock->expects(self::once())->method('send')->willReturnCallback(
            function (JsonRpcResponse $jsonRpcResponse): void {
                self::assertSame('theRequestId', $jsonRpcResponse->getId());
                self::assertNotNull($jsonRpcResponse->getError());
                self::assertSame(JsonRpcErrorCode::GENERIC_RUNTIME_ERROR, $jsonRpcResponse->getError()->getCode());
                self::assertSame('Exception message', $jsonRpcResponse->getError()->getMessage());
                self::assertSame(null, $jsonRpcResponse->getError()->getData());
            }
        );

        $this->jsonRpcQueueItemProcessor->process($queueItem);
    }
}
