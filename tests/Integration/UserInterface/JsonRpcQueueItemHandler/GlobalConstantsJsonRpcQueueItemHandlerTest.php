<?php

namespace Serenata\Tests\Integration\UserInterface\JsonRpcQueueItemHandler;

use Serenata\Tests\Integration\AbstractIntegrationTest;

final class GlobalConstantsJsonRpcQueueItemHandlerTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testGlobalConstants(): void
    {
        $output = $this->getGlobalConstants('GlobalConstants.phpt');

        self::assertThat($output, self::arrayHasKey('\DEFINE_CONSTANT'));
        self::assertSame($output['\DEFINE_CONSTANT']['name'], 'DEFINE_CONSTANT');
        self::assertSame($output['\DEFINE_CONSTANT']['fqcn'], '\DEFINE_CONSTANT');

        self::assertThat($output, self::arrayHasKey('\A\DEFINE_CONSTANT_NAMESPACED'));
        self::assertSame($output['\A\DEFINE_CONSTANT_NAMESPACED']['name'], 'DEFINE_CONSTANT_NAMESPACED');
        self::assertSame($output['\A\DEFINE_CONSTANT_NAMESPACED']['fqcn'], '\A\DEFINE_CONSTANT_NAMESPACED');

        self::assertThat($output, self::arrayHasKey('\A\FIRST_CONSTANT'));
        self::assertSame($output['\A\FIRST_CONSTANT']['name'], 'FIRST_CONSTANT');
        self::assertSame($output['\A\FIRST_CONSTANT']['fqcn'], '\A\FIRST_CONSTANT');

        self::assertThat($output, self::arrayHasKey('\A\SECOND_CONSTANT'));
        self::assertSame($output['\A\SECOND_CONSTANT']['name'], 'SECOND_CONSTANT');
        self::assertSame($output['\A\SECOND_CONSTANT']['fqcn'], '\A\SECOND_CONSTANT');

        self::assertThat($output, self::logicalNot(self::arrayHasKey('SHOULD_NOT_SHOW_UP')));
    }

    /**
     * @return void
     */
    public function testCorrectlyFetchesDefaultValueOfDefineWithExpression(): void
    {
        $output = $this->getGlobalConstants('DefineWithExpression.phpt');

        self::assertSame('(($version{0} * 10000) + ($version{2} * 100) + $version{4})', $output['\TEST_CONSTANT']['defaultValue']);
    }

    /**
     * @return void
     */
    public function testCorrectlyFetchesDefaultValueOfDefineWithIncompleteConstFetch(): void
    {
        $output = $this->getGlobalConstants('DefineWithIncompleteConstFetch.phpt');

        self::assertSame('\Test::', $output['\TEST_CONSTANT']['defaultValue']);
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private function getGlobalConstants(string $file): array
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('globalConstantsJsonRpcQueueItemHandler');

        return $command->getGlobalConstants();
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/GlobalConstantsJsonRpcQueueItemHandlerTest/' . $file;
    }
}
