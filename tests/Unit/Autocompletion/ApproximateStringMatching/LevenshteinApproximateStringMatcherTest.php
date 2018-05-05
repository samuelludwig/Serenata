<?php

namespace Serenata\Tests\Unit\Autocompletion\ApproximateStringMatching;

use Serenata\Autocompletion\ApproximateStringMatching\LevenshteinApproximateStringMatcher;

class LevenshteinApproximateStringMatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testScoresParametersThatRequireFewerInsertionsMoreFavorably(): void
    {
        $test1 = 'test1';
        $test2 = 'test12';
        $referenceText = 'test';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertTrue($matcher->score($test1, $referenceText) < $matcher->score($test2, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresParametersThatRequireFewerReplacementsMoreFavorably(): void
    {
        $test1 = 'veso';
        $test2 = 'vevo';
        $referenceText = 'test';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertTrue($matcher->score($test1, $referenceText) < $matcher->score($test2, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresParametersThatRequireFewerRemovalsMoreFavorably(): void
    {
        $test1 = 'testo';
        $test2 = 'testos';
        $referenceText = 'test';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertTrue($matcher->score($test1, $referenceText) < $matcher->score($test2, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresParametersThatContainSubstringMatchesInMiddleOfApproximationMoreFavorably(): void
    {
        $test1 = '\UnexpectedValueException';
        $test2 = '\SQLiteUnbuffered';
        $referenceText = 'Une';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertTrue($matcher->score($test1, $referenceText) < $matcher->score($test2, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresParametersThatContainSubstringMatchesAtEndOfApproximationMoreFavorably(): void
    {
        $test1 = '\UnexpectedValueException';
        $test2 = '\DoctrineTest\InstantiatorTest\Exception\UnexpectedValueExceptionTest';
        $referenceText = 'UnexpectedValueException';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertTrue($matcher->score($test1, $referenceText) < $matcher->score($test2, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresParametersThatContainExactMatchesOfApproximationMoreFavorably(): void
    {
        $test1 = 'UnexpectedValueException';
        $test2 = 'http\Exception\UnexpectedValueException';
        $referenceText = 'UnexpectedValueException';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertTrue($matcher->score($test1, $referenceText) < $matcher->score($test2, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresParametersThatContainAnExactMatchAsWordMoreFavorablyThanOnesWithSubwordMatch(): void
    {
        $test1 = 'Bar\JsonRedirectResponse';
        $test2 = 'A\JsonRedirectResponse2';
        $referenceText = 'JsonRedirectResponse';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertTrue($matcher->score($test1, $referenceText) < $matcher->score($test2, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresParametersThatContainExactMatchAtTheEndMoreFavorablyThanOnesInBetween(): void
    {
        $test1 = 'A\Foobar\Foobar';
        $test2 = 'A\Foobar\Stub';
        $referenceText = 'Foobar';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertTrue($matcher->score($test1, $referenceText) < $matcher->score($test2, $referenceText));
    }

    /**
     * @return void
     */
    public function testDoesNotAssignExtraFavorForParamtersThatContainExactMatchesOfApproximationMultipleTimes(): void
    {
        $test1 = 'UnexpectedValueException\UnexpectedValueException';
        $test2 = 'SomeEquallyLongNamespace\UnexpectedValueException';
        $referenceText = 'UnexpectedValueException';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertSame($matcher->score($test1, $referenceText), $matcher->score($test2, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresParametersThatAreTooFarApartAsNull(): void
    {
        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertNull($matcher->score('foo', 'test'));
        static::assertNull($matcher->score('Application\Test\FooBar\QuxProviderInterface', 'ActiveUserProviderInterface'));
    }

    /**
     * @return void
     */
    public function testDoesNotFailOnEmptyParameters(): void
    {
        $test = '';
        $referenceText = '';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertNotNull($matcher->score($test, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresApproximationThatIsTooLongAsNullInsteadOfShowingWarning(): void
    {
        $test = str_repeat('a', 100000);
        $referenceText = '1';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertNull($matcher->score($test, $referenceText));
    }

    /**
     * @return void
     */
    public function testScoresReferenceTextThatIsTooLongAsNullInsteadOfShowingWarning(): void
    {
        $test = '1';
        $referenceText = str_repeat('a', 100000);

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertNull($matcher->score($test, $referenceText));
    }
}
