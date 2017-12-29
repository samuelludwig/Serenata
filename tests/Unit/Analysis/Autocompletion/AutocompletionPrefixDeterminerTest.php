<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Autocompletion;

use PhpIntegrator\Analysis\Autocompletion\AutocompletionPrefixDeterminer;

class AutocompletionPrefixDeterminerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testFetchesPrefixTakingBoundaryCharactersIntoAccount(): void
    {
        $determiner = new AutocompletionPrefixDeterminer();

        static::assertSame('', $determiner->determine('hello', 0));
        static::assertSame('hell', $determiner->determine('hello', 4));
        static::assertSame('hello', $determiner->determine('hello', 5));
        static::assertSame('lo', $determiner->determine('hel+lo', 6));
        static::assertSame('lo', $determiner->determine("hel\nlo", 6));
        static::assertSame('lo', $determiner->determine("hel\tlo", 6));
        static::assertSame('lo', $determiner->determine('hel(lo', 6));
        static::assertSame('lo', $determiner->determine('hel)lo', 6));
        static::assertSame('lo', $determiner->determine('hel{lo', 6));
        static::assertSame('lo', $determiner->determine('hel}lo', 6));
        static::assertSame('lo', $determiner->determine('hel[lo', 6));
        static::assertSame('lo', $determiner->determine('hel]lo', 6));
        static::assertSame('lo', $determiner->determine('hel+lo', 6));
        static::assertSame('lo', $determiner->determine('hel-lo', 6));
        static::assertSame('lo', $determiner->determine('hel*lo', 6));
        static::assertSame('lo', $determiner->determine('hel/lo', 6));
        static::assertSame('lo', $determiner->determine('hel^lo', 6));
        static::assertSame('lo', $determiner->determine('hel|lo', 6));
        static::assertSame('lo', $determiner->determine('hel&lo', 6));
        static::assertSame('lo', $determiner->determine('hel:lo', 6));
        static::assertSame('lo', $determiner->determine('hel!lo', 6));
        static::assertSame('lo', $determiner->determine('hel?lo', 6));
        static::assertSame('lo', $determiner->determine('hel@lo', 6));
        static::assertSame('lo', $determiner->determine('hel#lo', 6));
        static::assertSame('lo', $determiner->determine('hel%lo', 6));
        static::assertSame('lo', $determiner->determine('hel>lo', 6));
        static::assertSame('lo', $determiner->determine('hel<lo', 6));
        static::assertSame('lo', $determiner->determine('hel=lo', 6));
        static::assertSame('lo', $determiner->determine('hel,lo', 6));
    }

    /**
     * @return void
     */
    public function testDoesNotSeeNamespaceSeparatorAsBoundaryCharacter(): void
    {
        $determiner = new AutocompletionPrefixDeterminer();;

        static::assertSame('hel\lo', $determiner->determine('hel\lo', 6));
    }
}
