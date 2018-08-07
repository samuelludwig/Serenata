<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

class StaticMethodAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'StaticMethodList.phpt';
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            'PropertyFetch.phpt',
            'MethodCall.phpt',
            'StaticMethodCall.phpt',
            'StaticMethodCallSelf.phpt',
            'StaticMethodCallParent.phpt',
            'ClassConstFetch.phpt',
            'ClassConstFetchNoDelimiter.phpt',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingStaticMethodAutocompletionProvider';
    }
}
