<?php

namespace PhpIntegrator\Tests\Analysis\Typing;

use PhpIntegrator\Analysis\Typing\TypeResolver;
use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

class TypeResolverTest extends \PHPUnit_Framework_TestCase
{
    protected function getTypeAnalyzer()
    {
        return new TypeAnalyzer();
    }

    protected function getTypeResolver()
    {
        return new TypeResolver($this->getTypeAnalyzer());
    }

    public function testEmptyTypeReturnsNull()
    {
        $object = $this->getTypeResolver();

        $this->assertNull($object->resolve(null, null, []));
    }

    public function testTypeWithLeadingSlashIsNotResolved()
    {
        $object = $this->getTypeResolver();

        $this->assertEquals('\A\B', $object->resolve('\A\B', null, []));
    }

    public function testRelativeTypeIsRelativeToNamespace()
    {
        $object = $this->getTypeResolver();

        $this->assertEquals('\A', $object->resolve('A', null, []));

        $object = $this->getTypeResolver();

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

        $object = $this->getTypeResolver();

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
        $object = $this->getTypeResolver();

        $object->resolve('SOME_CONSTANT', null, [], UseStatementKind::TYPE_CONSTANT);
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\TypeResolutionImpossibleException
     */
    public function testUnqualifiedConstantsWithNamespacePrefixGenerateException()
    {
        $object = $this->getTypeResolver();

        $object->resolve('A\SOME_CONSTANT', null, [], UseStatementKind::TYPE_CONSTANT);
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\TypeResolutionImpossibleException
     */
    public function testUnqualifiedFunctionsGenerateException()
    {
        $object = $this->getTypeResolver();

        $object->resolve('some_function', null, [], UseStatementKind::TYPE_FUNCTION);
    }

    /**
     * @expectedException \PhpIntegrator\Analysis\Typing\TypeResolutionImpossibleException
     */
    public function testUnqualifiedFunctionsWithNamespacePrefixGenerateException()
    {
        $object = $this->getTypeResolver();

        $object->resolve('A\some_function', null, [], UseStatementKind::TYPE_FUNCTION);
    }
}
