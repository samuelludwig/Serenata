<?php

namespace PhpIntegrator\Tests\Analysis\Typing;

use PhpIntegrator\Analysis\Typing\TypeResolver;
use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

class TypeResolverTest extends \PHPUnit_Framework_TestCase
{
    protected function createTypeAnalyzer()
    {
        return new TypeAnalyzer();
    }

    protected function createTypeResolver()
    {
        return new TypeResolver($this->createTypeAnalyzer());
    }

    public function testEmptyTypeReturnsNull()
    {
        $object = $this->createTypeResolver();

        $this->assertNull($object->resolve(null, null, []));
    }

    public function testTypeWithLeadingSlashIsNotResolved()
    {
        $object = $this->createTypeResolver();

        $this->assertEquals('\A\B', $object->resolve('\A\B', null, []));
    }

    public function testRelativeTypeIsRelativeToNamespace()
    {
        $object = $this->createTypeResolver();

        $this->assertEquals('\A', $object->resolve('A', null, []));

        $object = $this->createTypeResolver();

        $this->assertEquals('\A\B', $object->resolve('B', 'A', []));
    }

    public function testRelativeTypeIsRelativeToUseStatements()
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
     * @expectedException \PhpIntegrator\Analysis\Typing\TypeResolutionImpossibleException
     */
    public function testUnqualifiedConstantsGenerateException()
    {
        $object = $this->createTypeResolver();

        $object->resolve('SOME_CONSTANT', null, [], UseStatementKind::TYPE_CONSTANT);
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\TypeResolutionImpossibleException
     */
    public function testUnqualifiedConstantsWithNamespacePrefixGenerateException()
    {
        $object = $this->createTypeResolver();

        $object->resolve('A\SOME_CONSTANT', null, [], UseStatementKind::TYPE_CONSTANT);
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\TypeResolutionImpossibleException
     */
    public function testUnqualifiedFunctionsGenerateException()
    {
        $object = $this->createTypeResolver();

        $object->resolve('some_function', null, [], UseStatementKind::TYPE_FUNCTION);
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\TypeResolutionImpossibleException
     */
    public function testUnqualifiedFunctionsWithNamespacePrefixGenerateException()
    {
        $object = $this->createTypeResolver();

        $object->resolve('A\some_function', null, [], UseStatementKind::TYPE_FUNCTION);
    }
}
