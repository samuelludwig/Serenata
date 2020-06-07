<div align="center">
<a href="https://gitlab.com/Serenata/Serenata"><img src="https://assets.gitlab-static.net/uploads/-/system/project/avatar/2815601/PHP_Integrator.png" alt="Serenata" title="Serenata" width="258"></a>

<h1>Serenata</h1>
<h4>Gratis, libre and open source server providing code assistance for PHP</h4>

<a href="https://gitlab.com/Serenata/Serenata/commits/master">
    <img src="https://gitlab.com/Serenata/Serenata/badges/master/pipeline.svg">
</a>

<a href="https://gitlab.com/Serenata/Serenata/commits/master">
    <img src="https://gitlab.com/Serenata/Serenata/badges/master/coverage.svg">
</a>

<a href="https://serenata.gitlab.io/#support">
    <img src="https://img.shields.io/badge/€-Support-blue.svg?&amp;style=flat">
</a>
</div>

Serenata is a gratis, libre and open source server that performs static analysis on PHP codebases to provide code assistance, such as autocompletion, linting, code navigation and tooltips. It achieves its goal by speaking the [language server protocol](https://microsoft.github.io/language-server-protocol/) over sockets with [its clients](https://serenata.gitlab.io/#what-do-i-need).

Serenata was previously known as "PHP Integrator".

Please see [the wiki](https://gitlab.com/Serenata/Serenata/wikis/home) as well as [the website](https://serenata.gitlab.io/), which contain more information for users, both developers looking to implement clients as well as programmers using the server via editors and IDEs.

## Installation
### Stable
#### PHAR (recommended)
Download the latest stable PHAR for your PHP version [from the releases page](https://gitlab.com/Serenata/Serenata/-/tags).

#### Composer
```sh
composer create-project "serenata/serenata" serenata --prefer-dist --no-dev
```

### Unstable
You can find the latest _unstable_ builds as PHAR by downloading the artifacts [of the latest pipelines](https://gitlab.com/Serenata/Serenata/pipelines) or simply install latest master through Composer or by pulling from Git.

## Running
Most users will simply want to run Serenata through their favorite editor or IDE. See [the website](https://serenata.gitlab.io/#what-do-i-need) for a list of available clients and how to install them.

If you are writing a new client, please read the following sections.

### PHAR
```sh
php -d memory_limit=1024M distribution-7.x.phar --uri=tcp://127.0.0.1:11111
```

Where `x` is the PHP version you downloaded the PHAR for.

### Composer
```sh
php -d memory_limit=1024M <Serenata folder>/bin/console --uri=tcp://127.0.0.1:11111
```

### Command Line Arguments
#### Port
You can select any port you desire, as long as it is not in use on your system.

#### Host
`127.0.0.1` will run on `localhost`, which means the server will only be reachable from your local machine. This is usually what you want.

You can use other IP addresses such as `0.0.0.0` to make the server reachable across the network or inside a (e.g. Docker) container. (_The usual security lecture applies here, as anyone in the network can then connect to the server and request information about your codebase._)

### Performance
See [this section of the wiki](https://gitlab.com/Serenata/Serenata/wikis/Advanced%20Configuration).

## Use In Other Projects
If you want to make the server part of your (existing) project and use the classes contained inside it for your own purposes:

```sh
composer require "serenata/serenata"
```

Note that the server was designed primarily as an application and not as a library. However, it is still very much possible to instantiate the classes you need yourself.

You may also be interested in [other libraries that are part of the Serenata suite](https://gitlab.com/Serenata). In the future, more code may be split from the server into proper, separate libraries.

## Contributing
As this project is inherently large in scope, there is a lot of potential and a lot of areas to work in, so contributions are most welcome! Take a look at [our contribution guide](https://gitlab.com/Serenata/Serenata/blob/master/CONTRIBUTING.md).

![AGPLv3 Logo](https://www.gnu.org/graphics/agplv3-with-text-162x68.png)
