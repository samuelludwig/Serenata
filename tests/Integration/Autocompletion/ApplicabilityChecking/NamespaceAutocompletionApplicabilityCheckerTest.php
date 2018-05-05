<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

class NamespaceAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'NamespaceList.phpt';
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            'Namespace.phpt',
            'UseStatement.phpt'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingNamespaceAutocompletionProvider';
    }
}
