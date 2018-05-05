<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

class FunctionAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'FunctionList.phpt';
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            'TopLevelNamespace.phpt',
            'FunctionLike.phpt'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingFunctionAutocompletionProvider';
    }
}
