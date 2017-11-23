<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

class ApplicabilityCheckingFunctionAutocompletionProviderTest extends AbstractAutocompletionProviderTest
{
    /**
     * @dataProvider getFileNamesWhereShouldApply
     *
     * @param string $fileName
     *
     * @return void
     */
    public function testAppliesAtExpectedLocations(string $fileName): void
    {
        static::assertNotEmpty($this->provide($fileName));
    }

    /**
     * @dataProvider getFileNamesWhereShouldNotApply
     *
     * @param string $fileName
     *
     * @return void
     */
    public function testDoesNotApplyAtExpectedLocations(string $fileName): void
    {
        static::assertEmpty($this->provide($fileName));
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            ['TopLevelNamespace.phpt'],
            ['FunctionLike.phpt']
        ];
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldNotApply(): array
    {
        return [
            ['VariableName.phpt'],
            ['MethodCall.phpt'],
            ['StaticMethodCall.phpt'],
            ['ClassConstFetch.phpt'],
            ['UseStatement.phpt'],
            // ['FunctionSignature.phpt'],
            // ['MethodSignature.phpt'],
            ['PropertyFetch.phpt'],
            ['StaticPropertyFetch.phpt'],
            ['ClassBody.phpt'],
            ['String.phpt']
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'ApplicabilityCheckingFunctionAutocompletionProviderTest';
    }
}
