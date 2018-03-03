<div align="center">
    <a href="https://gitlab.com/php-integrator/core"><img src="https://assets.gitlab-static.net/uploads/-/system/project/avatar/2815601/PHP_Integrator.png" alt="PHP Integrator" title="PHP Integrator" width="258"></a>

    <h1>PHP Integrator - Core</h1>
    <h4>A server providing IDE-like features for PHP code bases to clients</h4>

    <a href="https://gitlab.com/php-integrator/core/commits/development">
        <img src="https://gitlab.com/php-integrator/core/badges/development/pipeline.svg">
    </a>

    <a href="https://gitlab.com/php-integrator/core/commits/development">
        <img src="https://gitlab.com/php-integrator/core/badges/development/coverage.svg">
    </a>

    <a href="https://liberapay.com/Gert-dev/donate">
        <img src="https://img.shields.io/badge/send_coffee_beans-Liberapay-blue.svg?&amp;style=flat">
    </a>
</div>

PHP Integrator is a free and open source server that indexes PHP code and performs static analysis. It stores its information in a database and can retrieve information about your code to clients by communicating over sockets. Clients can use this information to provide various functionalities, such as autocompletion, code navigation and tooltips.

More information for users, both developers looking to implement the core in other editors as well as programmers using it via editors and IDE's, can be found [on the wiki](https://gitlab.com/php-integrator/core/wikis/home) as well as [the website](https://php-integrator.github.io/).

## What Features Are Supported?
* Autocompletion
* Goto Definition (code navigation)
* Signature help (call tips)
* Tooltips
* Linting

There are also other requests clients can send to extract information about a code base. However, we are in the process of slowly migrating to become a [language server](https://microsoft.github.io/language-server-protocol/) for PHP, so these may be replaced by compliant requests in the future.

## Where Is It Used?
Currently the core package is used to power the php-integrator-* packages for the Atom editor. See also
[the list of projects](https://github.com/php-integrator).

## Installation
### Runtime
If you want to use the core directly, i.e. just to be able to fire it up and communicate with it over a socket, such as when you want to integrate it into an editor:

```sh
composer create-project "php-integrator/core" "php-integrator-core" --prefer-dist --no-dev
```

You can then run it with:

```sh
php -d memory_limit=1024M src/Main.php --port=11111
```

You can select any port you desire, as long as it is not in use on your system.

The memory limit can also be freely set. The memory needed very much depends on the size of the project, the PHP version as well as the operating system. To give you some idea, at the time of writing, when running the core on itself, it sits at around 150 MB on a 64-bit Linux system with PHP 7.1.

### Development
If you want to make the core part of your (existing) project and use the classes contained inside it for your own purposes:

```sh
composer require "php-integrator/core"
```

Note that the core was designed primarily as an application and not as a library. However, it is still very much possible to instantiate the classes you need yourself.

You may also be interested in [other libraries that are part of the php-integrator suite](https://gitlab.com/php-integrator). In the future, more code may be split from the core into proper, separate libraries.

## Contributing
As this project is inherently large in scope, there is a lot of potential and a lot of area's to work in, so contributions are most welcome! Take a look at [our contribution guide](https://gitlab.com/php-integrator/core/blob/development/CONTRIBUTING.md).

![GPLv3 Logo](https://gitlab.com/php-integrator/core/raw/793c93b0f69a5f4ba183f1dfff79f0c68d9bd010/resources/images/gpl_v3.png)
