<?php

namespace Serenata\Tests\Unit\Analysis\Typing\Deduction;

use DateTime;

use PhpParser\Node;

use PHPUnit\Framework\MockObject\MockObject;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\Deduction\NameNodeTypeDeducer;
use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;

use Serenata\Analysis\Typing\TypeNormalizerInterface;
use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Utility\TextDocumentItem;

class NameNodeTypeDeducerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NameNodeTypeDeducer
     */
    private $nameNodeTypeDeducer;

    /**
     * @var MockObject
     */
    private $typeNormalizerMock;

    /**
     * @var MockObject
     */
    private $classlikeInfoBuilderMock;

    /**
     * @var MockObject
     */
    private $fileClasslikeListProviderMock;

    /**
     * @var MockObject
     */
    private $structureAwareNameResolverFactoryMock;

    /**
     * @var MockObject
     */
    private $storageMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->typeNormalizerMock = $this->getMockBuilder(TypeNormalizerInterface::class)->getMock();

        $this->classlikeInfoBuilderMock = $this->getMockBuilder(
            ClasslikeInfoBuilderInterface::class
        )->getMock();

        $this->fileClasslikeListProviderMock = $this->getMockBuilder(
            FileClasslikeListProviderInterface::class
        )->getMock();

        $this->structureAwareNameResolverFactoryMock = $this->getMockBuilder(
            StructureAwareNameResolverFactoryInterface::class
        )->getMock();

        $this->storageMock = $this->getMockBuilder(
            StorageInterface::class
        )->getMock();

        $this->nameNodeTypeDeducer = new NameNodeTypeDeducer(
            $this->typeNormalizerMock,
            $this->classlikeInfoBuilderMock,
            $this->fileClasslikeListProviderMock,
            $this->structureAwareNameResolverFactoryMock,
            $this->storageMock
        );
    }

    /**
     * @return void
     */
    public function testParentInsideAnonymousClassInsideOtherClassSelectsAnonymousClassParent(): void
    {
        $node = new Node\Name('parent');
        // $node->setAttribute('startFilePos', 7);

        $this->classlikeInfoBuilderMock->expects($this->once())->method('build')->with('AnonymousClass')->willReturn([
            'parents' => ['ParentOfAnonymousClass']
        ]);

        $this->typeNormalizerMock
            ->method('getNormalizedFqcn')
            ->with('ParentOfAnonymousClass')
            ->willReturn('\\ParentOfAnonymousClass');

        $this->fileClasslikeListProviderMock->method('getAllForFile')->willReturn([
            'OuterClass' => [
                'range' => new Range(new Position(1, 0), new Position(5, 1))
            ],

            'AnonymousClass' => [
                'range' => new Range(new Position(2, 0), new Position(4, 1))
            ]
        ]);

        $code = "<?php\n\n\n\n";

        $position = new Position(3, 0);

        static::assertSame(
            ['\\ParentOfAnonymousClass'],
            $this->nameNodeTypeDeducer->deduce(
                new TypeDeductionContext($node, new TextDocumentItem('', $code), $position)
            )
        );
    }
}
