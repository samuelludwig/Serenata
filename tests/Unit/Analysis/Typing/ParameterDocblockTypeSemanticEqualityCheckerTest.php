<?php

namespace Serenata\Tests\Unit\Analysis\Typing;

use PHPUnit\Framework\MockObject\MockObject;

use Serenata\Analysis\ClasslikeInfoBuilder;

use Serenata\Analysis\Typing\ParameterDocblockTypeSemanticEqualityChecker;

use Serenata\DocblockTypeParser;
use Serenata\DocblockTypeParser\DocblockTypeParser as DocblockTypeParserActualParser;
use Serenata\DocblockTypeParser\DocblockTypeEquivalenceComparator;

use Serenata\NameQualificationUtilities\PositionalNameResolverInterface;
use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;
use PHPUnit\Framework\TestCase;

final class ParameterDocblockTypeSemanticEqualityCheckerTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $positionalNameResolverMock;

    /**
     * @var MockObject
     */
    private $docblockTypeParserMock;

    /**
     * @var MockObject
     */
    private $docblockTypeEquivalenceComparatorMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->positionalNameResolverMock = $this->getMockBuilder(PositionalNameResolverInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $this->docblockTypeParserMock = new DocblockTypeParserActualParser();
        $this->docblockTypeEquivalenceComparatorMock = new DocblockTypeEquivalenceComparator();
    }

    /**
     * @return void
     */
    public function testMatchingTypeNamePasses(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'int',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('int');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMismatchingTypeNameFails(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'bool',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('bool');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMatchingNullableTypePasses(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'int',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\IntDocblockType(),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('int');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMismatchingNullableTypeFails(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'int',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('int');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypesWithSameQualificationPasses(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('A'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\A', '\A');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypesWithDifferentQualificationPasses(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('\A\B'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\B\A', '\B\A');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testDifferentClassTypesFails(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock([
                [
                    'fqcn'       => '\A',
                ],
                [
                    'fqcn'       => '\A',
                    'parents'    => [],
                    'interfaces' => [],
                ],
            ]),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('B'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\A', '\B');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypeAllowsSpecializationByParent(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock([
                [
                    'fqcn'       => '\A',
                ],
                [
                    'fqcn'       => '\B',
                    'parents'    => ['\A'],
                    'interfaces' => [],
                ],
            ]),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('B'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\A', '\B');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypeAllowsSpecializationByInterface(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock([
                [
                    'fqcn'       => '\A',
                ],
                [
                    'fqcn'       => '\B',
                    'parents'    => [],
                    'interfaces' => ['\A'],
                ],
            ]),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('B'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\A', '\B');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypeAllowsMultipleSpecializationsByParentOrInterface(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock([
                [
                    'fqcn'       => '\A',
                ],
                [
                    'fqcn'       => '\B',
                    'parents'    => ['\A'],
                    'interfaces' => [],
                ],
                [
                    'fqcn'       => '\A',
                ],
                [
                    'fqcn'       => '\C',
                    'parents'    => [],
                    'interfaces' => ['\A'],
                ],
            ]),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\ClassDocblockType('B'),
                new DocblockTypeParser\ClassDocblockType('C')
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\A', '\B', '\C');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testVariadicParameterRequiresArrayTypeHintAndPassesWhenItIsPresent(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock([
                [
                    'fqcn'       => '\A',
                ],
                [
                    'fqcn'       => '\A',
                    'parents'    => [],
                    'interfaces' => [],
                ],
            ]),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => true,
            'isNullable'  => false,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\SpecializedArrayDocblockType(
                new DocblockTypeParser\ClassDocblockType('A')
            ),
            'description' => null,
            'isVariadic'  => true,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\A', '\A');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testVariadicParameterRequiresArrayTypeHintAndailsWhenItIsMissing(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock([
                [
                    'fqcn'       => '\A',
                ],
                [
                    'fqcn'       => '\A',
                    'parents'    => [],
                    'interfaces' => [],
                ],
            ]),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => true,
            'isNullable'  => false,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('A'),
            'description' => null,
            'isVariadic'  => true,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\A', '\A');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testVariadicParameterWithDifferentQualification(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock([
                [
                    'fqcn'       => '\B\A',
                ],
                [
                    'fqcn'       => '\B\A',
                    'parents'    => [],
                    'interfaces' => [],
                ],
            ]),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => true,
            'isNullable'  => false,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\SpecializedArrayDocblockType(
                new DocblockTypeParser\ClassDocblockType('\B\A')
            ),
            'description' => null,
            'isVariadic'  => true,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\B\A', '\B\A');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeAllowsSpecialization(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\SpecializedArrayDocblockType(
                new DocblockTypeParser\IntDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeDoesNotAllowOtherTypes(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeWithMatchingNullabilityPasses(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\ArrayDocblockType(),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    // /**
    //  * @return void
    //  */
    // public function testArrayTypeAllowsSpecializationInCombinationWithOtherTypes(): void
    // {
    //     $checker = new ParameterDocblockTypeSemanticEqualityChecker(
    //         $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
    //         $this->getClasslikeInfoBuilderMock(),
    //         $this->docblockTypeParserMock,
    //         $this->docblockTypeEquivalenceComparatorMock
    //     );
    //
    //     $parameter = [
    //         'isReference' => false,
    //         'isVariadic'  => false,
    //         'isNullable'  => true,
    //         'type'        => 'array'
    //     ];
    //
    //     $docblockParameter = [
    //         'type'        => new DocblockTypeParser\SpecializedArrayDocblockType(
    //             new DocblockTypeParser\IntDocblockType(),
    //             new DocblockTypeParser\NullDocblockType()
    //         ),
    //         'description' => null,
    //         'isVariadic'  => false,
    //         'isReference' => false
    //     ];
    //
    //     $this->positionalNameResolverMock->method('resolve')->willReturn('array');
    //     static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    // }

    /**
     * @return void
     */
    public function testClassTypeAllowsSpecializationByParentInCombinationWithOtherTypes(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock([
                [
                    'fqcn'       => '\A',
                ],
                [
                    'fqcn'       => '\B',
                    'parents'    => ['\A'],
                    'interfaces' => [],
                ],
                [
                    'fqcn'       => '\A',
                ],
                [
                    'fqcn'       => '\C',
                    'parents'    => ['\A'],
                    'interfaces' => [],
                ],
            ]),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'A',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\ClassDocblockType('B'),
                new DocblockTypeParser\ClassDocblockType('C'),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('\A', '\B', '\C');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableBoolTypeWithMatchingNullabilityPassesEvenIfDocblockOrderIsReversed(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'bool',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\NullDocblockType(),
                new DocblockTypeParser\BoolDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('bool');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeWithMismatchingNullabilityFails(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ArrayDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeWithMismatchingNullabilityFails(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\ArrayDocblockType(),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeAllowsSpecialization(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\IntDocblockType()
                ),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeAllowsMultipleSpecializations(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\IntDocblockType()
                ),
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\FloatDocblockType()
                )
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeAllowsMultipleSpecializationsButFailsWhenAnotherTypeIsPresent(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\IntDocblockType()
                ),
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\FloatDocblockType()
                ),
                new DocblockTypeParser\BoolDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeAllowsMultipleSpecializationsButFailsWhenAnotherTypeIsPresentAndThatTypeIsNull(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\IntDocblockType()
                ),
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\FloatDocblockType()
                ),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeAllowsMultipleSpecializations(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\IntDocblockType()
                ),
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\FloatDocblockType()
                ),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeAllowsMultipleSpecializationsButFailsWhenNullIsMissing(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => 'int[]|float[]',
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\IntDocblockType()
                ),
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\FloatDocblockType()
                )
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMatchingReferenceTypesPasses(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => true,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'int',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => true,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('int');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMismatchingReferenceTypesFails(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => true,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'int',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('int');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeWithParanthesizedSpecialization(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\SpecializedArrayDocblockType(
                new DocblockTypeParser\CompoundDocblockType(
                    new DocblockTypeParser\IntDocblockType(),
                    new DocblockTypeParser\FloatDocblockType()
                )
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeWithParanthesizedSpecialization(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\SpecializedArrayDocblockType(
                    new DocblockTypeParser\CompoundDocblockType(
                        new DocblockTypeParser\IntDocblockType(),
                        new DocblockTypeParser\FloatDocblockType()
                    )
                ),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('array');
        static::assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMismatchingClassTypeAndStringDocblockTypeFailsButDoesNotGenerateError(): void
    {
        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockStructureAwareNameResolverFactory($this->positionalNameResolverMock),
            $this->getClasslikeInfoBuilderMock(),
            $this->docblockTypeParserMock,
            $this->docblockTypeEquivalenceComparatorMock
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'Foo',
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\StringDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false,
        ];

        $this->positionalNameResolverMock->method('resolve')->willReturn('Foo');
        static::assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @param PositionalNameResolverInterface $structureAwareNameResolverMock
     *
     * @return StructureAwareNameResolverFactoryInterface
     */
    private function mockStructureAwareNameResolverFactory(
        PositionalNameResolverInterface $structureAwareNameResolverMock
    ): StructureAwareNameResolverFactoryInterface {
        $resolver = $this->getMockBuilder(StructureAwareNameResolverFactoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $resolver->method('create')->will($this->returnValue($structureAwareNameResolverMock));

        return $resolver;
    }

    /**
     * @param mixed[] $returnValues
     *
     * @return ClasslikeInfoBuilder
     */
    private function getClasslikeInfoBuilderMock(array $returnValues = []): ClasslikeInfoBuilder
    {
        $classlikeInfoBuilder = $this->getMockBuilder(ClasslikeInfoBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['build'])
            ->getMock();

        if (!empty($returnValues)) {
            $classlikeInfoBuilder->method('build')->willReturn(...$returnValues);
        }

        return $classlikeInfoBuilder;
    }
}
