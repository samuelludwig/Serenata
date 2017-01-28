<?php

namespace PhpIntegrator\Tests\Analysis\Typing;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Resolving\TypeResolver;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

class TypeResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return TypeAnalyzer
     */
    protected function createTypeAnalyzer(): TypeAnalyzer
    {
        return new TypeAnalyzer();
    }

    /**
     * @return TypeResolver
     */
    protected function createTypeResolver(): TypeResolver
    {
        return new TypeResolver($this->createTypeAnalyzer());
    }

    /**
     * @return void
     */
    public function testEmptyTypeReturnsNull(): void
    {
        $object = $this->createTypeResolver();

        $this->assertNull($object->resolve('', null, []));
    }

    /**
     * @return void
     */
    public function testTypeWithLeadingSlashIsNotResolved(): void
    {
        $object = $this->createTypeResolver();

        $this->assertEquals('\A\B', $object->resolve('\A\B', null, []));
    }

    /**
     * @return void
     */
    public function testRelativeTypeIsRelativeToNamespace(): void
    {
        $object = $this->createTypeResolver();

        $this->assertEquals('\A', $object->resolve('A', null, []));

        $object = $this->createTypeResolver();

        $this->assertEquals('\A\B', $object->resolve('B', 'A', []));
    }

    /**
     * @return void
     */
    public function testRelativeTypeIsRelativeToUseStatements(): void
    {
        $namespace = 'A';
        $imports = [
            [
                'name'  => 'B\C',
                'alias' => 'Alias',
                'kind'  => UseStatementKind::TYPE_CLASSLIKE
            ],

            [
                'name'  => 'B\C\D',
                'alias' => 'D',
                'kind'  => UseStatementKind::TYPE_CLASSLIKE
            ]
        ];

        $object = $this->createTypeResolver();

        $this->assertEquals('\B\C', $object->resolve('Alias', $namespace, $imports));
        $this->assertEquals('\B\C\E', $object->resolve('Alias\E', $namespace, $imports));
        $this->assertEquals('\B\C\D', $object->resolve('D', $namespace, $imports));
        $this->assertEquals('\B\C\D\E', $object->resolve('D\E', $namespace, $imports));
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\Resolving\TypeResolutionImpossibleException
     */
    public function testUnqualifiedConstantsGenerateException(): void
    {
        $object = $this->createTypeResolver();

        $object->resolve('SOME_CONSTANT', null, [], UseStatementKind::TYPE_CONSTANT);
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\Resolving\TypeResolutionImpossibleException
     */
    public function testUnqualifiedConstantsWithNamespacePrefixGenerateException(): void
    {
        $object = $this->createTypeResolver();

        $object->resolve('A\SOME_CONSTANT', null, [], UseStatementKind::TYPE_CONSTANT);
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\Resolving\TypeResolutionImpossibleException
     */
    public function testUnqualifiedFunctionsGenerateException(): void
    {
        $object = $this->createTypeResolver();

        $object->resolve('some_function', null, [], UseStatementKind::TYPE_FUNCTION);
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\Resolving\TypeResolutionImpossibleException
     */
    public function testUnqualifiedFunctionsWithNamespacePrefixGenerateException(): void
    {
        $object = $this->createTypeResolver();

        $object->resolve('A\some_function', null, [], UseStatementKind::TYPE_FUNCTION);
    }
}
