<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

final class ClassConstantAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'ClassConstantList.phpt';
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
            'ClassConstFetch.phpt',
            'ClassConstFetchNoDelimiter.phpt',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingClassConstantAutocompletionProvider';
    }
}
