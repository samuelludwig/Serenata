<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Autocompletion\ApproximateStringMatching;

use PhpIntegrator\Analysis\Autocompletion\ApproximateStringMatching\LevenshteinApproximateStringMatcher;

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
        $test1 = 'teso';
        $test2 = 'tevo';
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
    public function testScoresParametersThatAreTooFarApartAsNull(): void
    {
        $test = 'foo';
        $referenceText = 'test';

        $matcher = new LevenshteinApproximateStringMatcher();

        static::assertNull($matcher->score($test, $referenceText));
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
}
