<?php

namespace Serenata\Tests\Unit\Autocompletion\ApproximateStringMatching;

use ArrayObject;

use PHPUnit\Framework\TestCase;

use Serenata\Autocompletion\ApproximateStringMatching\ApproximateStringMatcherInterface;
use Serenata\Autocompletion\ApproximateStringMatching\ApproximateStringMatchingBestStringApproximationDeterminer;

final class ApproximateStringMatchingBestStringApproximationDeterminerTest extends TestCase
{
    /**
     * @return void
     */
    public function testSortsResultsByScore(): void
    {
        $approximateStringMatcher = $this->getMockBuilder(ApproximateStringMatcherInterface::class)
            ->setMethods(['score'])
            ->getMock();

        $approximateStringMatcher->method('score')->willReturn(2, 1);

        $determiner = new ApproximateStringMatchingBestStringApproximationDeterminer($approximateStringMatcher);

        $items = [
            ['key' => 'worstMatch'],
            ['key' => 'bestMatch'],
        ];

        static::assertSame(
            [$items[1], $items[0]],
            $determiner->determine($items, 'referenceText', 'key', null)
        );
    }

    /**
     * @return void
     */
    public function testFiltersOutResultsWithUnacceptableScore(): void
    {
        $approximateStringMatcher = $this->getMockBuilder(ApproximateStringMatcherInterface::class)
            ->setMethods(['score'])
            ->getMock();

        $approximateStringMatcher->method('score')->willReturn(null, 1);

        $determiner = new ApproximateStringMatchingBestStringApproximationDeterminer($approximateStringMatcher);

        $items = [
            ['key' => 'worstMatch'],
            ['key' => 'bestMatch'],
        ];

        static::assertSame(
            [$items[1]],
            $determiner->determine($items, 'referenceText', 'key', null)
        );
    }

    /**
     * @return void
     */
    public function testDoesNotFilterOutItemsWithSameScore(): void
    {
        $approximateStringMatcher = $this->getMockBuilder(ApproximateStringMatcherInterface::class)
            ->setMethods(['score'])
            ->getMock();

        $approximateStringMatcher->method('score')->willReturn(1, 1);

        $determiner = new ApproximateStringMatchingBestStringApproximationDeterminer($approximateStringMatcher);

        $items = [
            ['key' => 'first'],
            ['key' => 'second'],
        ];

        static::assertSame(
            [$items[0], $items[1]],
            $determiner->determine($items, 'referenceText', 'key', null)
        );
    }

    /**
     * @return void
     */
    public function testDoesNotReturnMoreThanRequestedAmountOfItems(): void
    {
        $approximateStringMatcher = $this->getMockBuilder(ApproximateStringMatcherInterface::class)
            ->setMethods(['score'])
            ->getMock();

        $approximateStringMatcher->method('score')->willReturn(2, 1);

        $determiner = new ApproximateStringMatchingBestStringApproximationDeterminer($approximateStringMatcher);

        $items = [
            ['key' => 'worstMatch'],
            ['key' => 'bestMatch'],
        ];

        static::assertSame(
            [$items[1]],
            $determiner->determine($items, 'referenceText', 'key', 1)
        );
    }

    /**
     * @return void
     */
    public function testHandlesArrayAccessObjects(): void
    {
        $approximateStringMatcher = $this->getMockBuilder(ApproximateStringMatcherInterface::class)
            ->setMethods(['score'])
            ->getMock();

        $approximateStringMatcher->method('score')->willReturn(1, 1);

        $determiner = new ApproximateStringMatchingBestStringApproximationDeterminer($approximateStringMatcher);

        $items = [
            new ArrayObject(['key' => 'test']),
        ];

        static::assertSame(
            [$items[0]],
            $determiner->determine($items, 'referenceText', 'key', null)
        );
    }

    /**
     * @return void
     */
    public function testDoesNotFailWhenNoItemsArePassed(): void
    {
        $approximateStringMatcher = $this->getMockBuilder(ApproximateStringMatcherInterface::class)
            ->setMethods(['score'])
            ->getMock();

        $approximateStringMatcher->method('score')->willReturn(1, 1);

        $determiner = new ApproximateStringMatchingBestStringApproximationDeterminer($approximateStringMatcher);

        $items = [];

        static::assertSame(
            [],
            $determiner->determine($items, 'referenceText', 'key', null)
        );
    }
}
