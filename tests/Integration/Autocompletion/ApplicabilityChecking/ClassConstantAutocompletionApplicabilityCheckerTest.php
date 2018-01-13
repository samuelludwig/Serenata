<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion\ApplicabilityChecking;

class ClassConstantAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
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
            'StaticMethodCallParent.phpt'
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
