<?php

namespace Foo\Bar\Baz {
    const QUX = 5;
}

namespace {
    use const Foo\Bar// <MARKER>
}
