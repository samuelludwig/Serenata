## 1.3.0
### Features and improvements
* A great deal of refactoring has occurred, which paved the way for performance improvements in several areas, such as type deduction.
  * Indexing should be slightly faster.
  * Everything should feel a bit more responsive.
  * Semantic linting should be significantly faster, especially for large files.
* The type of built-in global constants is now deduced from their default value as Reflection can't be used to fetch their type nor do we have any documentation data about them..
* A new command, `--namespace-list`, is now available, which can optionally be filtered by file, to retrieve a list of namespaces. (thanks to [pszczekutowicz](https://github.com/pszczekutowicz))
* When a class has a method that overrides a base class method and implements an interface method from one of its own interfaces, both the `implementation` and `override` data will now be set as they are both relevant.

### Bugs fixed
* Documentation for built-in functions was escaping underscores with a slash.
* Built-in interface methods had `isAbstract` set to `true` instead of `false`.
* Semantic linting was incorrectly processing unqualified global function names.
* Semantic linting was incorrectly processing unqualified global constant names.
* The status bar was not showing progress when a project index happened through a repository status change.
* Editing a file that did not meet the allowed extensions specified in the project settings still caused it to be added to the index.
* Parantheses inside strings were sometimes interfering with invocation info information, causing the wrong information to be returned.
* Previously a fix was applied to make FQCN's actually contain a leading slash to clearly indicate that they were fully qualified. This still didn't happen everywhere, which has been corrected now.
* Caching will now add an additional folder with the name of the active user in it. This solves a problem where instances from multiple users on the same system would try to use the same cache entries.
* Parent members of built-in classlikes were being indexed twice: once for the parent and once for the child, which was resulting in incorrect inheritance resolution results, unnecessary data storage and a (minor) performance hit.
* Built-in interfaces no longer have `isAbstract` set to true. They _are_ abstract in a certain sense, but this property is meant to indicate if a classlike has been defined using the abstract keyword. It was also not consistent with the behavior for non-built-in interfaces.

## 1.2.0
* Initial split from the [php-integrator/atom-base](https://github.com/php-integrator/atom-base) repository. See [its changelog](https://github.com/php-integrator/atom-base/blob/master/CHANGELOG.md) for what changed in older versions.
