# Contributing
A PHP static analyzer and server on top of that are both projects with a rather large scope. As such, pull requests are most welcome to improve any area that you'd like.

If you would like to contribute, but don't have any itch of your own to scratch, [the issue list](https://gitlab.com/php-integrator/core/issues) provides an overview of open issues and feature requests that could benefit from new development. Apart from these, there may also be improvements [in the Atom packages](https://github.com/php-integrator) that require work in the core itself.

## Code Style
The code base follows the PSR-2 coding style and the PSR-4 standard for namespacing. If you'd like to make changes, but are not familiar with PSR-2, [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer) is a tool that you can integrate with most IDE's and editors that will show styling problems to you early on.

There is also a `.editorconfig` file in the root of the project. If you don't have an editor that directly supports them, make sure you match its settings to it before you submit the code in a merge request.

## Tests
Most area's of the code contain either unit tests or integration tests. If you fix a bug with an existing feature or add a new feature, please also provide the appropriate tests for them.

There are some small areas left that currently don't have tests, such as the actual commands and the application, which are mostly facades to functionality that is tested. (Tests for these areas are, of course, very welcome, too!)

### Running Tests
Tests use the omnipresent PHPUnit, which is installed as dev dependency by Composer. To execute them, just run it:

```
./vendor/bin/phpunit
```

### Performance Tests
There are some simple performance tests, which don't run by default, but are handy if you want to quickly and roughly test a difference in performance. These are all part of the group `Performance`:

```
./vendor/bin/phpunit --group=Performance
```

### Unit Or Integration Tests?
Unit tests usually much more exhaustively test all parts of a single class, so they are never a lost effort. but there are some locations where it may be much easier to integration test instead. An example are locations that require a tree hierarchy of AST nodes to see if they are correctly processed by e.g. tooltips or signature help. (Unit testing these would require building the tree manually.)
