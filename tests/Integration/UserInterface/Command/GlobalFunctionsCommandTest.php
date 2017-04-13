<?php

namespace PhpIntegrator\Tests\Integration\UserInterface\Command;

use PhpIntegrator\UserInterface\Command\GlobalFunctionsCommand;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class GlobalFunctionsCommandTest extends AbstractIntegrationTest
{
        /**
         * @return void
         */
    public function testGlobalFunctions(): void
    {
        $path = __DIR__ . '/GlobalFunctionsCommandTest/' . 'GlobalFunctions.phpt';

        $container = $this->createTestContainer();

        $this->indexTestFile($container, $path);

        $command = $container->get('globalFunctionsCommand');

        $output = $command->getGlobalFunctions();

        $this->assertThat($output, $this->arrayHasKey('\A\firstFunction'));
        $this->assertEquals($output['\A\firstFunction']['name'], 'firstFunction');
        $this->assertEquals($output['\A\firstFunction']['fqcn'], '\A\firstFunction');
        $this->assertThat($output, $this->arrayHasKey('\A\secondFunction'));
        $this->assertEquals($output['\A\secondFunction']['name'], 'secondFunction');
        $this->assertEquals($output['\A\secondFunction']['fqcn'], '\A\secondFunction');
        $this->assertThat($output, $this->logicalNot($this->arrayHasKey('shouldNotShowUp')));
    }

    /**
     * @return void
     */
    public function testBuiltinGlobalFunctions(): void
    {
        $container = $this->createTestContainerForBuiltinStructuralElements();

        $command = new GlobalFunctionsCommand(
            $container->get('globalFunctionsProvider')
        );

        $output = $command->getGlobalFunctions();

        $this->assertArraySubset([
            'name'             => 'urlencode',
            'fqcn'             => '\urlencode',
            'startLine'        => 0,
            'endLine'          => 0,
            'filename'         => null,
            'isBuiltin'        => true,
            'isDeprecated'     => false,
            'hasDocblock'      => false,
            'hasDocumentation' => false,

            'throws'           => [],
            'returnTypeHint'   => null
        ], $output['\urlencode']);

        $this->assertArraySubset([
            [
                'name'         => 'str',
                'typeHint'     => null,
                'types'        => [
                    [
                        'type'         => 'string',
                        'fqcn'         => '\string',
                        'resolvedType' => '\string'
                    ]
                ],
                'defaultValue' => null,
                'isNullable'   => false,
                'isReference'  => false,
                'isVariadic'   => false,
                'isOptional'   => false,
            ]
        ], $output['\urlencode']['parameters']);

        $this->assertEquals([
            [
                'fqcn'         => '\string',
                'resolvedType' => '\string',
                'type'         => 'string'
            ]
        ], $output['\urlencode']['returnTypes']);

        $this->assertEquals([
            'name'         => 'index_key',
            'typeHint'     => null,
            'types'        => [
                [
                    'type'         => 'mixed',
                    'fqcn'         => '\mixed',
                    'resolvedType' => '\mixed'
                ]
            ],
            'defaultValue' => 'null',
            'isNullable'   => true,
            'isReference'  => false,
            'isVariadic'   => false,
            'isOptional'   => true,
            'description'  => 'The column to use as the index/keys for the returned array. This value may be the integer key of the column, or it may be the string key name.'
        ], $output['\array_column']['parameters'][2]);
    }
}
