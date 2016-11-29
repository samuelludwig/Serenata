## 2.0.0
### Major changes
* PHP 5.6 is now required. PHP 5.5 has been end of life for a couple of months now.
  * If you're running the server and upgrading is truly not an option at the moment, you can temporarily switch back the version check in the Main.php file as currently no PHP 5.6 features are used yet. However, in due time, they might.
* A great deal of refactoring has occurred, which paved the way for performance improvements in several areas, such as type deduction.
  * Indexing should be slightly faster.
  * Everything should feel a bit more responsive.
  * Semantic linting should be significantly faster, especially for large files.
* Passing command line arguments is no longer supported and has been replaced with a socket server implementation. This offers various benefits:
  * Bootstrapping is performed only once, allowing for responses from the server with lower latency.
  * Only a single process is managing a single database. This should solve the problems that some users had with the database suddenly being locked or unwritable.
  * Only a single process is spawned. No more spawning concurrent processes to perform different tasks, which might heavily burden the CPU on a user's system as well as has a lot of overhead.
    * Sockets will also naturally queue requests, so they are handled one by one as soon as the server is ready.
  * Caching is no longer performed via file caching, but entirely in memory. This means users that don't want to, don't know how to, or can't set up a tmpfs or ramdisk will now also benefit from the better performance of memory caching.
    * Additionally this completely obsoletes the need for wonky file locks and concurrent cache file access.

### Commands
* A new command, `namespaceList`, is now available, which can optionally be filtered by file, to retrieve a list of namespaces. (thanks to [pszczekutowicz](https://github.com/pszczekutowicz))
* `resolveType` and `localizeType` now require a `kind` parameter to determine the kind of the type (or rather: name) that needs to be resolved.
  * This is necessary to distinguish between classlike, constant and function name resolving based on use statements. (Yes, duplicate use statements may exist in PHP, as long as their `kind` is different).
* `implementation` changed to `implementations` because the data returned must be an array instead of a single value. The reasoning behind this is that a method can in fact implement multiple interface methods simultaneously (as opposed to just one).
* The `truncate` command was merged into the `initialize` command. To reinitialize a project, simply send the initialize command a second time.
* `invocationInfo` will now also return the name of the invoked function, method or constructor's class.
* `invocationInfo` now returns `method` instead of `function` for class methods (as opposed to global functions).
* `deduceTypes` now expects the full expression to be passed via the new `expression` parameter. The `part` parameter has been removed.

### Global functions and constants
* Unqualified global constants and functions will now correctly be resolved.
* Semantic linting was incorrectly processing unqualified global function and constant names.
* Use statements for constants (i.e. `use const`) and functions (i.e. `use function`) will now be properly analyzed when checking for unused use statements.

### Docblocks and documentation
* In documentation for built-in functions, underscores in the description were incorrectly escaped with a slash.
* In single line docblocks, the terminator `*/` was not being ignored (and taken up in the last tag in the docblock).
* Class annotations were sometimes being picked up as being part of the description of other tags (such as `@var`, `@param`, ...).
* `@noinspection` is no longer linted as invalid tag, so you can now not be blinded by errors when reading the code of a colleague using PhpStorm.
* Variadic parameters with type hints were incorrectly matched with their docblock types and, by consequence, incorrectly reported as having a mismatching type.

### Other
* Attempting to index a file that did not meet the passed allowed extensions still caused it to be added to the index.
* Assigning a global constant to something caused the type of that left operand to become the name of the constant instead.
* The `class` member that each class has since PHP 5.5 (that evaluates to its FQCN) is now returned along with class constant data.
* Use statements were incorrectly reported as unused when they were being used as extension or implementation for anonymous classes.
* PHP setups with the `cli.pager` option set will now no longer duplicate JSON output. (thanks to [molovo](https://github.com/molovo))
* Parantheses inside strings were sometimes interfering with invocation info information, causing the wrong information to be returned.
* The type of built-in global constants is now deduced from their default value as Reflection can't be used to fetch their type nor do we have any documentation data about them.
* Previously a fix was applied to make FQCN's actually contain a leading slash to clearly indicate that they were fully qualified. This still didn't happen everywhere, which has been corrected now.
* When a class has a method that overrides a base class method and implements an interface method from one of its own interfaces, both the `implementation` and `override` data will now be set as they are both relevant.
* Parent members of built-in classlikes were being indexed twice: once for the parent and once for the child, which was resulting in incorrect inheritance resolution results, unnecessary data storage and a (minor) performance hit.
* Built-in interfaces no longer have `isAbstract` set to true. They _are_ abstract in a certain sense, but this property is meant to indicate if a classlike has been defined using the abstract keyword. It was also not consistent with the behavior for non-built-in interfaces.
  * Built-in interface methods also had `isAbstract` set to `true` instead of `false`.

* The indexer was assigning an incorrect type to variadic parameters. You can now use elements of type hinted variadic parameters as expected in a foreach:

```php
protected function foo(Bar ...$bars)
{
    foreach ($bars as $bar) {
        // $bar is now an instance of Bar.
    }
}
```

* The type deducer can now (finally) cope with conditionals on properties, next to variables:

```php
class Foo
{
    /**
     * @var \Iterator
     */
    protected $bar;

    public function fooMethod()
    {
        // Before:
        $this->bar = new \SplObjectStorage();
        $this->bar-> // Still lists members of Iterator.

        if ($this->bar instanceof \DateTime) {
            $this->bar-> // Still lists members of Iterator.
        }

        // After:
        $this->bar = new \SplObjectStorage();
        $this->bar-> // Lists members of SplObjectStorage.

        if ($this->bar instanceof \DateTime) {
            $this->bar-> // Lists members of DateTime.
        }
    }
}
```

* Type deduction with conditionals has improved in many ways, for example:

```php
if ($a instanceof A || $a instanceof B) {
    if ($a instanceof A) {
        // $a is now correctly A instead of A|B.
    }
}
```

```php
$b = '';

if ($b) {
    // $b is now correctly string instead of string|bool.
}
```

## 1.2.0
* Initial split from the [php-integrator/atom-base](https://github.com/php-integrator/atom-base) repository. See [its changelog](https://github.com/php-integrator/atom-base/blob/master/CHANGELOG.md) for what changed in older versions.
