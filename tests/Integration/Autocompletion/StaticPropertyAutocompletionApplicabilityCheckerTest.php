<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion;

class StaticPropertyAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'StaticPropertyList.phpt';
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            'StaticMethodCall.phpt',
            'StaticMethodCallSelf.phpt',
            'StaticMethodCallParent.phpt',
            'StaticPropertyFetch.phpt',
            'StaticPropertyFetchError.phpt',
            'StaticPropertyFetchSelf.phpt',
            'StaticPropertyFetchSelfError.phpt',
            'StaticPropertyFetchStatic.phpt',
            'StaticPropertyFetchStaticError.phpt',
            'StaticPropertyFetchParent.phpt',
            'StaticPropertyFetchParentError.phpt'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingStaticPropertyAutocompletionProvider';
    }
}
