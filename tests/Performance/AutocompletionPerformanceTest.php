<?php

namespace Serenata\Tests\Performance;

use Serenata\Autocompletion\Providers\AutocompletionProviderContext;

use Serenata\Common\Position;

use Serenata\Sockets\JsonRpcRequest;

use Serenata\Utility\InitializeParams;
use Serenata\Utility\TextDocumentItem;

/**
 * @group Performance
 */
final class AutocompletionPerformanceTest extends AbstractPerformanceTest
{
    /**
     * @return void
     */
    public function testProvideAllFromStubs(): void
    {
        $uriToIndex = 'file://' . $this->normalizePath(__DIR__ . '/../../vendor/jetbrains/phpstorm-stubs');
        $dummyDatabaseUri = $this->getOutputDirectory() . '/test-stubs.sqlite';

        @unlink($dummyDatabaseUri);

        $this->getActiveWorkspaceManager()->setActiveWorkspace(null);
        $this->container->get('managerRegistry')->setDatabaseUri($dummyDatabaseUri);
        $this->container->get('initializeJsonRpcQueueItemHandler')->initialize(
            new InitializeParams(
                123,
                null,
                $uriToIndex,
                [
                    'configuration' => [
                        'uris'                    => [$uriToIndex],
                        'indexDatabaseUri'        => $dummyDatabaseUri,
                        'phpVersion'              => 7.1,
                        'excludedPathExpressions' => [],
                        'fileExtensions'          => ['php'],
                    ],
                ],
                [],
                null,
                []
            ),
            $this->mockJsonRpcMessageSenderInterface(),
            new JsonRpcRequest('TESTID', 'NOT USED'),
            false
        );

        $this->indexPath($this->container, $uriToIndex);

        $testFilePath = $uriToIndex . '/Core/Core_d.php';
        $code = $this->container->get('sourceCodeStreamReader')->getSourceCodeFromFile($testFilePath);

        $positionThatWillGenerateNonEmptyPrefix = strpos($code, "define ('E_ERROR', 1);");

        // Empty prefixes are a specially optimized case that we don't want to trigger to have more realistic results.
        self::assertTrue(
            $positionThatWillGenerateNonEmptyPrefix !== false,
            'No location found that would generate a non-empty prefix'
        );

        $positionThatWillGenerateNonEmptyPrefix += strlen('d');
        $position = Position::createFromByteOffset($positionThatWillGenerateNonEmptyPrefix, $code, 'UTF-8');

        $time = $this->time(function () use ($testFilePath, $code, $position): void {
            $suggestions = $this->container->get('autocompletionProvider')->provide(
                new AutocompletionProviderContext(new TextDocumentItem($testFilePath, $code), $position, 'd')
            );

            $suggestionItems = iterator_to_array($suggestions);

            self::assertNotEmpty($suggestionItems);
        });

        unlink($dummyDatabaseUri);

        $this->finish($time);
    }
}
