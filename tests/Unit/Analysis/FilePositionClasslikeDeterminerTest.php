<?php

namespace Serenata\Tests\Unit\Analysis;

use PHPUnit\Framework\MockObject\MockObject;

use PHPUnit\Framework\TestCase;

use Serenata\Analysis\FilePositionClasslikeDeterminer;

use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\StorageInterface;

use Serenata\Utility\TextDocumentItem;

final class FilePositionClasslikeDeterminerTest extends TestCase
{
    /**
     * @var FilePositionClasslikeDeterminer
     */
    private $filePositionClasslikeDeterminer;

    /**
     * @var MockObject&FileClasslikeListProviderInterface
     */
    private $fileClasslikeListProviderMock;

    /**
     * @var MockObject&StorageInterface
     */
    private $storageMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileClasslikeListProviderMock = $this->getMockBuilder(
            FileClasslikeListProviderInterface::class
        )->getMock();

        $this->storageMock = $this->getMockBuilder(
            StorageInterface::class
        )->getMock();

        $this->filePositionClasslikeDeterminer = new FilePositionClasslikeDeterminer(
            $this->fileClasslikeListProviderMock,
            $this->storageMock
        );
    }

    /**
     * @return void
     */
    public function testAnonymousClassInsideOtherClassSelectsAnonymousClass(): void
    {
        $this->fileClasslikeListProviderMock->method('getAllForFile')->willReturn([
            '\OuterClass' => [
                'range' => new Range(new Position(1, 0), new Position(5, 1)),
            ],

            '\AnonymousClass' => [
                'range' => new Range(new Position(2, 0), new Position(4, 1)),
            ],
        ]);

        $code = "<?php\n\n\n\n";

        $position = new Position(3, 0);

        static::assertSame(
            '\\AnonymousClass',
            $this->filePositionClasslikeDeterminer->determine(
                new TextDocumentItem('', $code),
                $position
            )
        );
    }
}
