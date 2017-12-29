<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

class ClassConstantAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionProviderTest
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
        static::assertNotEmpty($this->provide($fileName, 'ClassConstantList.phpt'));
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
        static::assertEmpty($this->provide($fileName, 'ClassConstantList.phpt'));
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            ['StaticMethodCall.phpt'],
            ['StaticMethodCallSelf.phpt'],
            ['StaticMethodCallParent.phpt']
        ];
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldNotApply(): array
    {
        return [
            ['VariableName.phpt'],
            ['ClassConstFetch.phpt'],
            ['UseStatement.phpt'],
            // ['Docblock.phpt'],
            // ['Comment.phpt'],
            ['FunctionSignature.phpt'],
            ['MethodSignature.phpt'],
            ['ClassBody.phpt'],
            ['String.phpt'],
            ['TopLevelNamespace.phpt'],
            ['FunctionLike.phpt'],
            ['PropertyFetch.phpt'],
            ['MethodCall.phpt'],
            ['StaticPropertyFetch.phpt'],
            ['StaticPropertyFetchError.phpt'],
            ['StaticPropertyFetchSelf.phpt'],
            ['StaticPropertyFetchSelfError.phpt'],
            ['StaticPropertyFetchStatic.phpt'],
            ['StaticPropertyFetchStaticError.phpt'],
            ['StaticPropertyFetchParent.phpt'],
            ['StaticPropertyFetchParentError.phpt']
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
        return 'applicabilityCheckingClassConstantAutocompletionProvider';
    }
}
