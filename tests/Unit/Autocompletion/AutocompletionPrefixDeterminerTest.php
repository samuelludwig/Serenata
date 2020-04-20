<?php

namespace Serenata\Tests\Unit\Autocompletion;

use PHPUnit_Framework_MockObject_MockObject;

use PHPUnit\Framework\TestCase;

use Serenata\Autocompletion\AutocompletionPrefixDeterminer;
use Serenata\Autocompletion\AutocompletionPrefixBoundaryTokenRetrieverInterface;

use Serenata\Common\Position;

final class AutocompletionPrefixDeterminerTest extends TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $boundaryTokenRetrieverMock;

    /**
     * @inheritDoc
     */
    public function setUp()
    {
        $this->boundaryTokenRetrieverMock = $this->getMockBuilder(
            AutocompletionPrefixBoundaryTokenRetrieverInterface::class
        )
            ->setMethods(['retrieve'])
            ->getMock();
    }

    /**
     * @return void
     */
    public function testFetchesPrefixTakingBoundaryCharactersIntoAccount(): void
    {
        $this->boundaryTokenRetrieverMock->method('retrieve')->willReturn(['+']);

        $determiner = new AutocompletionPrefixDeterminer($this->boundaryTokenRetrieverMock);

        static::assertSame('', $determiner->determine('hello', new Position(0, 0)));
        static::assertSame('hell', $determiner->determine('hello', new Position(0, 4)));
        static::assertSame('hello', $determiner->determine('hello', new Position(0, 5)));
        static::assertSame('lo', $determiner->determine('hel+lo', new Position(0, 6)));
    }

    /**
     * @return void
     */
    public function testDoesNotSeeNamespaceSeparatorAsBoundaryCharacter(): void
    {
        $this->boundaryTokenRetrieverMock->method('retrieve')->willReturn(['+']);

        $determiner = new AutocompletionPrefixDeterminer($this->boundaryTokenRetrieverMock);

        static::assertSame('hel\lo', $determiner->determine('hel\lo', new Position(0, 6)));
    }
}
