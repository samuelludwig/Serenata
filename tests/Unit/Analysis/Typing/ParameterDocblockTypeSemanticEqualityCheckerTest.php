<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Typing;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Typing\ParameterDocblockTypeSemanticEqualityChecker;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolver;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactory;

use PhpIntegrator\DocblockTypeParser;

class ParameterDocblockTypeSemanticEqualityCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testMatchingTypeNamePasses(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('int');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMismatchingTypeNameFails(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'bool'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('bool');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMatchingNullableTypePasses(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\IntDocblockType(),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('int');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMismatchingNullableTypeFails(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('int');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypesWithSameQualificationPasses(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('A'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('\A', '\A');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypesWithDifferentQualificationPasses(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('\A\B'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('\B\A', '\B\A');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testDifferentClassTypesFails(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock([
                [
                    'name'       => '\A'
                ],
                [
                    'name'       => '\A',
                    'parents'    => [],
                    'interfaces' => []
                ]
            ])
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('B'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('\A', '\B');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypeAllowsSpecializationByParent(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock([
                [
                    'name'       => '\A'
                ],
                [
                    'name'       => '\B',
                    'parents'    => ['\A'],
                    'interfaces' => []
                ]
            ])
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('B'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('\A', '\B');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypeAllowsSpecializationByInterface(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock([
                [
                    'name'       => '\A'
                ],
                [
                    'name'       => '\B',
                    'parents'    => [],
                    'interfaces' => ['\A']
                ]
            ])
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('B'),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('\A', '\B');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testClassTypeAllowsMultipleSpecializationsByParentOrInterface(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock([
                [
                    'name'       => '\A'
                ],
                [
                    'name'       => '\B',
                    'parents'    => ['\A'],
                    'interfaces' => []
                ],
                [
                    'name'       => '\A'
                ],
                [
                    'name'       => '\C',
                    'parents'    => [],
                    'interfaces' => ['\A']
                ]
            ])
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\ClassDocblockType('B'),
                new DocblockTypeParser\ClassDocblockType('C')
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('\A', '\B', '\C');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testVariadicParameterRequiresArrayTypeHintAndPassesWhenItIsPresent(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock([
                [
                    'name'       => '\A'
                ],
                [
                    'name'       => '\A',
                    'parents'    => [],
                    'interfaces' => []
                ]
            ])
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => true,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\SpecializedArrayDocblockType(
                new DocblockTypeParser\ClassDocblockType('A')
            ),
            'description' => null,
            'isVariadic'  => true,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('\A', '\A');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testVariadicParameterRequiresArrayTypeHintAndailsWhenItIsMissing(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock([
                [
                    'name'       => '\A'
                ],
                [
                    'name'       => '\A',
                    'parents'    => [],
                    'interfaces' => []
                ]
            ])
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => true,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ClassDocblockType('A'),
            'description' => null,
            'isVariadic'  => true,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('\A', '\A');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testVariadicParameterWithDifferentQualification(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock([
                [
                    'name'       => '\B\A'
                ],
                [
                    'name'       => '\B\A',
                    'parents'    => [],
                    'interfaces' => []
                ]
            ])
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => true,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\SpecializedArrayDocblockType(
                new DocblockTypeParser\ClassDocblockType('\B\A')
            ),
            'description' => null,
            'isVariadic'  => true,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('\B\A', '\B\A');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeAllowsSpecialization(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\SpecializedArrayDocblockType(
                new DocblockTypeParser\IntDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeDoesNotAllowOtherTypes(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeWithMatchingNullabilityPasses(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\ArrayDocblockType(),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeWithMismatchingNullabilityFails(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\ArrayDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeWithMismatchingNullabilityFails(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\CompoundDocblockType(
                new DocblockTypeParser\ArrayDocblockType(),
                new DocblockTypeParser\NullDocblockType()
            ),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeAllowsSpecialization(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
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
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeAllowsMultipleSpecializations(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
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
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeAllowsMultipleSpecializationsButFailsWhenAnotherTypeIsPresent(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
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
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeAllowsMultipleSpecializationsButFailsWhenAnotherTypeIsPresentAndThatTypeIsNull(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
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
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeAllowsMultipleSpecializations(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
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
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeAllowsMultipleSpecializationsButFailsWhenNullIsMissing(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
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
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMatchingReferenceTypesPasses(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => true,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => true
        ];

        $fileTypeResolver->method('resolve')->willReturn('int');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMismatchingReferenceTypesFails(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => true,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\IntDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('int');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testArrayTypeWithParanthesizedSpecialization(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
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
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testNullableArrayTypeWithParanthesizedSpecialization(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
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
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array');
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @return void
     */
    public function testMismatchingClassTypeAndStringDocblockTypeFailsButDoesNotGenerateError(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getClasslikeInfoBuilderMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'Foo'
        ];

        $docblockParameter = [
            'type'        => new DocblockTypeParser\StringDocblockType(),
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('Foo');
        $this->assertFalse($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
    }

    /**
     * @param FileTypeResolver $fileTypeResolverMock
     *
     * @return FileTypeResolverFactory
     */
    protected function mockFileTypeResolverFactory(FileTypeResolver $fileTypeResolverMock): FileTypeResolverFactory
    {
        $fileTypeResolverFactory = $this->getMockBuilder(FileTypeResolverFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $fileTypeResolverFactory->method('create')->will($this->returnValue($fileTypeResolverMock));

        return $fileTypeResolverFactory;
    }

    /**
     * @param mixed[] $returnValues
     *
     * @return ClasslikeInfoBuilder
     */
    protected function getClasslikeInfoBuilderMock(array $returnValues = []): ClasslikeInfoBuilder
    {
        $classlikeInfoBuilder = $this->getMockBuilder(ClasslikeInfoBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getClasslikeInfo'])
            ->getMock();

        if (!empty($returnValues)) {
            $classlikeInfoBuilder->method('getClasslikeInfo')->willReturn(...$returnValues);
        }

        return $classlikeInfoBuilder;
    }
}
