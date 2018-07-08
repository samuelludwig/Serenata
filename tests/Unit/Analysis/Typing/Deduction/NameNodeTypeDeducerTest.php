<?php

namespace Serenata\Tests\Unit\Analysis\Typing\Deduction;

use DateTime;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Indexing\Structures;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use PHPUnit\Framework\MockObject\MockObject;

use Serenata\Analysis\Typing\TypeNormalizerInterface;
use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Analysis\Typing\Deduction\NameNodeTypeDeducer;

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

        $this->nameNodeTypeDeducer = new NameNodeTypeDeducer(
            $this->typeNormalizerMock,
            $this->classlikeInfoBuilderMock,
            $this->fileClasslikeListProviderMock,
            $this->structureAwareNameResolverFactoryMock
        );
    }

    /**
     * @return void
     */
    public function testParentInsideAnonymousClassInsideOtherClassSelectsAnonymousClassParent(): void
    {
        $node = new Node\Name('parent');

        $file = new Structures\File('', new DateTime(), []);

        $this->classlikeInfoBuilderMock->expects($this->once())->method('build')->with('AnonymousClass')->willReturn([
            'parents' => ['ParentOfAnonymousClass']
        ]);

        $this->typeNormalizerMock
            ->method('getNormalizedFqcn')
            ->with('ParentOfAnonymousClass')
            ->willReturn('\\ParentOfAnonymousClass');

        $this->fileClasslikeListProviderMock->method('getAllForFile')->willReturn([
            'OuterClass' => [
                'startLine' => 1,
                'endLine'   => 5
            ],

            'AnonymousClass' => [
                'startLine' => 2,
                'endLine'   => 4
            ]
        ]);

        $code = "<?php\n\n\n\n";

        static::assertSame(['\\ParentOfAnonymousClass'], $this->nameNodeTypeDeducer->deduce($node, $file, $code, 7));
    }
}
