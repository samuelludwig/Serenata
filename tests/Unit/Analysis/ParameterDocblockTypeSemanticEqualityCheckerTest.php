<?php

namespace PhpIntegrator\Tests\Unit\Analysis;

use PhpIntegrator\Analysis\ParameterDocblockTypeSemanticEqualityChecker;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolver;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactory;

class ParameterDocblockTypeSemanticEqualityCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testSingleType(): void
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

        $fileTypeResolver->method('resolve')->will($this->returnValue('int'));
        $this->assertTrue($checker->isEqual($parameter, $docblockParameter, 'ignored', 1));
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
