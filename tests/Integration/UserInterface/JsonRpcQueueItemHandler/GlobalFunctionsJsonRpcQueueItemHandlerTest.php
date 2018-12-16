<?php

namespace Serenata\Tests\Integration\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class GlobalFunctionsJsonRpcQueueItemHandlerTest extends AbstractIntegrationTest
{
        /**
         * @return void
         */
    public function testGlobalFunctions(): void
    {
        $path = __DIR__ . '/GlobalFunctionsJsonRpcQueueItemHandlerTest/' . 'GlobalFunctions.phpt';

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('globalFunctionsJsonRpcQueueItemHandler');

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
