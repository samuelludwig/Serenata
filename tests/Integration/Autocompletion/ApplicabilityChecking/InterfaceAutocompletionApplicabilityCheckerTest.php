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
            'FunctionLike.phpt',
            'ParameterType.phpt',
            'InterfaceExtends.phpt',
            'Implements.phpt',
            'UseStatement.phpt'
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
