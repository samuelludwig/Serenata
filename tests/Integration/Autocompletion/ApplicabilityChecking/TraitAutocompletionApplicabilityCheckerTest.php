<?php

namespace Serenata\Tests\Integration\Autocompletion\ApplicabilityChecking;

class TraitAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionApplicabilityCheckerTest
{
    /**
     * @inheritDoc
     */
    protected function getFileNameOfFileContainingSuggestionSources(): ?string
    {
        return 'TraitList.phpt';
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            'TopLevelNamespace.phpt',
            'FunctionLike.phpt',
            'TraitUse.phpt',
            'UseStatement.phpt',
            'StaticMethodCallClassName.phpt',
            'ParameterDefaultValue.phpt',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingTraitAutocompletionProvider';
    }
}
