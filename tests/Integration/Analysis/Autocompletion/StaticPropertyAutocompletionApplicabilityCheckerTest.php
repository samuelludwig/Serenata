<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Autocompletion;

class StaticPropertyAutocompletionApplicabilityCheckerTest extends AbstractAutocompletionProviderTest
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
        static::assertNotEmpty($this->provide($fileName, 'StaticPropertyList.phpt'));
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
        static::assertEmpty($this->provide($fileName, 'StaticPropertyList.phpt'));
    }

    /**
     * @return string[]
     */
    public function getFileNamesWhereShouldApply(): array
    {
        return [
            ['StaticMethodCall.phpt'],
            ['StaticPropertyFetch.phpt'],
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
            ['MethodCall.phpt']
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
        return 'applicabilityCheckingStaticPropertyAutocompletionProvider';
    }
}
