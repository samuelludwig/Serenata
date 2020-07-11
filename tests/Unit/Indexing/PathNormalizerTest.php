<?php

namespace Serenata\Tests\Unit\Indexing;

use PHPUnit\Framework\TestCase;

use Serenata\Indexing\PathNormalizer;

final class PathNormalizerTest extends TestCase
{
    /**
     * @return void
     */
    public function testNormalizeWindowsPath(): void
    {
        $path = 'C:\Path\To\file.ext';
        $normalized = (new PathNormalizer())->normalize($path);

        self::assertEquals('C:/Path/To/file.ext', $normalized);
    }

    /**
     * @return void
     */
    public function testNormalizeWindowsMixedPath(): void
    {
        $path = 'C:\Path/To\Other/file.ext';
        $normalized = (new PathNormalizer())->normalize($path);

        self::assertEquals('C:/Path/To/Other/file.ext', $normalized);
    }

    /**
     * @return void
     */
    public function testNormalizeWindowsFilePath(): void
    {
        $path = 'file://C:\Path\To\file.ext';
        $normalized = (new PathNormalizer())->normalize($path);

        self::assertEquals('file://C:/Path/To/file.ext', $normalized);
    }

    /**
     * @return void
     */
    public function testUnixPath(): void
    {
        $path = '/path/to/file.ext';
        $normalized = (new PathNormalizer())->normalize($path);

        self::assertEquals($path, $normalized);
    }

    /**
     * @return void
     */
    public function testRelativeUnixPath(): void
    {
        $path = 'relative/path/to/file.ext';
        $normalized = (new PathNormalizer())->normalize($path);

        self::assertEquals($path, $normalized);
    }

    /**
     * @return void
     */
    public function testUnixUri(): void
    {
        $path = 'file:///relative/path/to/file.ext';
        $normalized = (new PathNormalizer())->normalize($path);

        self::assertEquals($path, $normalized);
    }
}
