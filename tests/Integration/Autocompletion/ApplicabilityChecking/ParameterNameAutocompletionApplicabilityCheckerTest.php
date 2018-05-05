<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

class ParameterNameAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return null;
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            'ParameterName.phpt'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingParameterNameAutocompletionProvider';
    }
}
