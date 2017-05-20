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
}
