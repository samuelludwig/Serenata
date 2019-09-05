<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

final class LocalVariableAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'LocalVariableList.phpt';
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            'TopLevelNamespace.phpt',
            'FunctionLike.phpt',
            'VariableName.phpt',
            'New.phpt',
            'Clone.phpt',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingLocalVariableAutocompletionProvider';
    }
}
