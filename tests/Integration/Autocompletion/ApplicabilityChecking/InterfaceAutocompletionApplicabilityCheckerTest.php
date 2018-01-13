<?php

namespace PhpIntegrator\Tests\Integration\Autocompletion\ApplicabilityChecking;

class InterfaceAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'InterfaceList.phpt';
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
        return 'applicabilityCheckingInterfaceAutocompletionProvider';
    }
}
