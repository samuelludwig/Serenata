<?php

namespace PhpIntegrator\Tests\Integration\UserInterface\Command;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class GlobalFunctionsCommandTest extends AbstractIntegrationTest
{
        /**
         * @return void
         */
    public function testGlobalFunctions(): void
    {
        $path = __DIR__ . '/GlobalFunctionsCommandTest/' . 'GlobalFunctions.phpt';

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('globalFunctionsCommand');

        $output = $command->getGlobalFunctions();

        static::assertThat($output, $this->arrayHasKey('\A\firstFunction'));
        static::assertSame($output['\A\firstFunction']['name'], 'firstFunction');
        static::assertSame($output['\A\firstFunction']['fqcn'], '\A\firstFunction');
        static::assertThat($output, $this->arrayHasKey('\A\secondFunction'));
        static::assertSame($output['\A\secondFunction']['name'], 'secondFunction');
        static::assertSame($output['\A\secondFunction']['fqcn'], '\A\secondFunction');
        static::assertThat($output, $this->logicalNot($this->arrayHasKey('shouldNotShowUp')));
    }
}
