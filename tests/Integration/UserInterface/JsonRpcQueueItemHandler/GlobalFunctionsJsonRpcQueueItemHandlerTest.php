<?php

namespace Serenata\Tests\Integration\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class GlobalFunctionsJsonRpcQueueItemHandlerTest extends AbstractIntegrationTest
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

        self::assertThat($output, self::arrayHasKey('\A\firstFunction'));
        self::assertSame($output['\A\firstFunction']['name'], 'firstFunction');
        self::assertSame($output['\A\firstFunction']['fqcn'], '\A\firstFunction');
        self::assertThat($output, self::arrayHasKey('\A\secondFunction'));
        self::assertSame($output['\A\secondFunction']['name'], 'secondFunction');
        self::assertSame($output['\A\secondFunction']['fqcn'], '\A\secondFunction');
        self::assertThat($output, self::logicalNot(self::arrayHasKey('shouldNotShowUp')));
    }
}
