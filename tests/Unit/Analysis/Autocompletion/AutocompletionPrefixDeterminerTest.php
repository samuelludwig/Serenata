<?php

namespace PhpIntegrator\Tests\Unit\Analysis\Autocompletion;

use PHPUnit_Framework_MockObject_MockObject;

use PhpIntegrator\Analysis\Autocompletion\AutocompletionPrefixDeterminer;
use PhpIntegrator\Analysis\Autocompletion\AutocompletionPrefixBoundaryTokenRetrieverInterface;

class AutocompletionPrefixDeterminerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $boundaryTokenRetrieverMock;

    /// @inherited
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

        static::assertSame('', $determiner->determine('hello', 0));
        static::assertSame('hell', $determiner->determine('hello', 4));
        static::assertSame('hello', $determiner->determine('hello', 5));
        static::assertSame('lo', $determiner->determine('hel+lo', 6));
    }

    /**
     * @return void
     */
    public function testDoesNotSeeNamespaceSeparatorAsBoundaryCharacter(): void
    {
        $this->boundaryTokenRetrieverMock->method('retrieve')->willReturn(['+']);

        $determiner = new AutocompletionPrefixDeterminer($this->boundaryTokenRetrieverMock);

        static::assertSame('hel\lo', $determiner->determine('hel\lo', 6));
    }
}
