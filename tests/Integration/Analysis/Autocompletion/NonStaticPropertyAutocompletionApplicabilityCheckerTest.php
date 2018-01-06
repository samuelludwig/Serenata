<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

class NonStaticPropertyAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionProviderTest
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
        static::assertNotEmpty($this->provide($fileName, 'PropertyList.phpt'));
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
        static::assertEmpty($this->provide($fileName, 'PropertyList.phpt'));
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            ['PropertyFetch.phpt'],
            ['MethodCall.phpt']
        ];
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldNotApply(): array
    {
        return [
            ['VariableName.phpt'],
            ['StaticMethodCall.phpt'],
            ['ClassConstFetch.phpt'],
            ['ClassConstFetchNoDelimiter.phpt'],
            ['UseStatement.phpt'],
            ['Docblock.phpt'],
            ['Comment.phpt'],
            ['FunctionSignature.phpt'],
            ['MethodSignature.phpt'],
            ['ClassBody.phpt'],
            ['String.phpt'],
            ['TopLevelNamespace.phpt'],
            ['FunctionLike.phpt'],
            ['StaticMethodCallSelf.phpt'],
            ['StaticMethodCallParent.phpt'],
            ['StaticPropertyFetch.phpt'],
            ['StaticPropertyFetchError.phpt'],
            ['StaticPropertyFetchSelf.phpt'],
            ['StaticPropertyFetchSelfError.phpt'],
            ['StaticPropertyFetchStatic.phpt'],
            ['StaticPropertyFetchStaticError.phpt'],
            ['StaticPropertyFetchParent.phpt'],
            ['StaticPropertyFetchParentError.phpt'],
            ['ParameterName.phpt']
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getFolderName(): string
    {
        return 'ApplicabilityCheckingAutocompletionProviderTest';
    }

    /**
     * @inheritDoc
     */
    protected function getProviderName(): string
    {
        return 'applicabilityCheckingNonStaticPropertyAutocompletionProvider';
    }
}
