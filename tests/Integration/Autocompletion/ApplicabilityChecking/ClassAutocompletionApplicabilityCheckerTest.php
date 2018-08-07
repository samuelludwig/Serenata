<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

class ClassAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'ClassList.phpt';
    }

    /**
     * @inheritDoc
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            'TopLevelNamespace.phpt',
            'FunctionLike.phpt',
            'ParameterType.phpt',
            'ParameterDefaultValue.phpt',
            'New.phpt',
            'ClassExtends.phpt',
            'UseStatement.phpt',
            'ClassConstFetchClassName.phpt',
            'StaticMethodCallClassName.phpt',
            'StaticPropertyFetchClassName.phpt',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingClassAutocompletionProvider';
    }
}
