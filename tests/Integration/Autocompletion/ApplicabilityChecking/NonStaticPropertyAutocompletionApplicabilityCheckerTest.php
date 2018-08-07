<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

class NonStaticPropertyAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'PropertyList.phpt';
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            'PropertyFetch.phpt',
            'MethodCall.phpt',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingNonStaticPropertyAutocompletionProvider';
    }
}
