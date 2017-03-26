<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Typing;

use PhpIntegrator\Analysis\Typing\ParameterDocblockTypeSemanticEqualityChecker;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolver;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactory;

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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => 'int',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('int', 'int');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'bool'
        ];

        $docblockParameter = [
            'type'        => 'int',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('bool', 'int');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => 'int|null',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('int', 'int', 'null');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => 'int',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('int', 'int', 'null');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => 'A',
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => '\B\A',
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => 'B',
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
    public function testVariadicParameterRequiresArrayTypeHintAndPassesWhenItIsPresent(): void
    {
        $fileTypeResolver = $this->getMockBuilder(FileTypeResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $checker = new ParameterDocblockTypeSemanticEqualityChecker(
            $this->mockFileTypeResolverFactory($fileTypeResolver),
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => true,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => 'A[]',
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => true,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => 'A',
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => true,
            'isNullable'  => false,
            'type'        => 'A'
        ];

        $docblockParameter = [
            'type'        => '\B\A[]',
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'int[]',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'int');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'int',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'int');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'array|null',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'array', 'null');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'array',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'array');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'array|null',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'array');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'int[]|null',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'int', 'null');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'int[]|float[]',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'int', 'float');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'int[]|float[]|bool',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'int', 'float', 'bool');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'int[]|float[]|null',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'int', 'float', 'null');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'int[]|float[]|null',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'int', 'float', 'null');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => false,
            'isVariadic'  => false,
            'isNullable'  => true,
            'type'        => 'array'
        ];

        $docblockParameter = [
            'type'        => 'int[]|float[]',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('array', 'int', 'float');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => true,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => 'int',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => true
        ];

        $fileTypeResolver->method('resolve')->willReturn('int', 'int');
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
            $this->getTypeAnalyzerMock()
        );

        $parameter = [
            'isReference' => true,
            'isVariadic'  => false,
            'isNullable'  => false,
            'type'        => 'int'
        ];

        $docblockParameter = [
            'type'        => 'int',
            'description' => null,
            'isVariadic'  => false,
            'isReference' => false
        ];

        $fileTypeResolver->method('resolve')->willReturn('int', 'int');
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
     * @return TypeAnalyzer
     */
    protected function getTypeAnalyzerMock(): TypeAnalyzer
    {
        return new TypeAnalyzer();
    }
}
