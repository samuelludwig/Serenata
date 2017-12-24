# Contributing
A PHP static analyzer and server on top of that are both projects with a rather large scope. As such, pull requests are most welcome to improve any area that you'd like.

If you would like to contribute, but don't have any itch of your own to scratch, [the issue list](https://gitlab.com/php-integrator/core/issues) provides an overview of open issues and feature requests that could benefit from new development. Apart from these, there may also be improvements [in the Atom packages](https://github.com/php-integrator) that require work in the core itself.

## Code Style
The code base follows the PSR-2 coding style and the PSR-4 standard for namespacing. If you'd like to make changes, but are not familiar with PSR-2, [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer) is a tool that you can integrate with most IDE's and editors that will show styling problems to you early on.

There is also a `.editorconfig` file in the root of the project. If you don't have an editor that directly supports them, make sure you match its settings to it before you submit the code in a merge request.

## Tests
Most area's of the code contain either unit tests or integration tests. If you fix a bug with an existing feature or add a new feature, please also provide the appropriate tests for them.

There are some small areas left that currently don't have tests, such as the actual commands and the application, which are mostly facades to functionality that is tested. (Tests for these areas are, of course, very welcome, too!)

### PHPUnit
Tests use the omnipresent PHPUnit, which is installed as dev dependency by Composer. To execute them, just run it:

```sh
./vendor/bin/phpunit
```

### Paratest (Parallel PHPUnit)
If you own a processor that supports running multiple threads concurrently, as is rather common nowadays, you can also replace PHPUnit with [paratest](https://github.com/brianium/paratest) to run the tests in parallel:

```sh
./vendor/bin/paratest -p8 --exclude-group=Performance
```

Here the `8` in `-p8` is the number of processes that can be spawned at once. Usually this is set to the amount of threads your processor can handle simultaneously.

`paratest` doesn't seem to exclude the groups excluded in `phpunit.xml`, so the Performance group must be disabled explicitly.

### Performance Tests
There are some simple performance tests, which don't run by default, but are handy if you want to quickly and roughly test a difference in performance. These are all part of the group `Performance`:

```sh
./vendor/bin/phpunit --group=Performance
```

### Unit Or Integration Tests?
Unit tests usually much more exhaustively test all parts of a single class, so they are never a lost effort. but there are some locations where it may be much easier to integration test instead. An example are locations that require a tree hierarchy of AST nodes to see if they are correctly processed by e.g. tooltips or signature help. (Unit testing these would require building the tree manually.)

## How Do I Test Full Stack?
It can seem challenging to develop the core and test the changes in a real world scenario. Using Atom as an example, the easiest is probably to do the following:

1. Set up the core from Git somewhere (install composer dependencies, ensure tests work, ...)
2. Set up the Atom packages from Git in `~/.atom/packages` and run `apm install` in their folders to install their dependencies
  * Optionally, you can also just clone them somewhere else and symlink these folders into the Atom packages directory.
3. Go into the base package's `core` subfolder and symlink the core's Git repository folder to a new folder symlink with the name of [the core version specification used by the base package](https://github.com/php-integrator/atom-base/blob/master/lib/Main.coffee#L161)
  * To put this more plainly, symlink e.g. `php-integrator-base/core/3.0.0`, or whatever the version used by the base package is, to the core folder you pulled from Git

To summarize all of this, just replace the packages that are installed by Atom with their Git variants and replace the core the base package automatically downloads with the core from Git. Alternatively, you can set up a Git repository with the appropriate remotes in the existing folders.

### Atom Dev Mode
Rather than place the packages in `~/.atom/packages`, you can also use `~/.atom/dev/packages`. These packages are only loaded if Atom is in dev mode, which can also be automatically configured on a per-project basis with `atom-project-manager`.
