<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

class DocblockTagAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
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
            'DocblockTag.phpt'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingDocblockTagAutocompletionProvider';
    }
}
