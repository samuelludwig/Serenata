# php-integrator/core
[![pipeline status](https://gitlab.com/php-integrator/core/badges/development/pipeline.svg)](https://gitlab.com/php-integrator/core/commits/development) [![coverage report](https://gitlab.com/php-integrator/core/badges/development/coverage.svg)](https://gitlab.com/php-integrator/core/commits/development) :coffee: Send me some coffee beans via [Liberapay](https://liberapay.com/Gert-dev/donate) or [PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YKTNLZCRHMRTJ)

PHP Integrator is a server that indexes PHP code and performs static analysis. It stores its information in a database
and can retrieve information about your code to clients by communicating over sockets. Clients can use this information
to provide various functionalities, such as autocompletion, code navigation and tooltips.

More information for users, both developers looking to implement the core in other editors as well as programmers using it via editors and IDE's, can be found [on the wiki](https://gitlab.com/php-integrator/core/wikis/home) as well as [the website](https://php-integrator.github.io/).

## Where Is It Used?
Currently the core package is used to power the php-integrator-* packages for the Atom editor. See also
[the list of projects](https://github.com/php-integrator).

## Installation
### Runtime
If you want to use the core directly, i.e. just to be able to fire it up and communicate with it over a socket, such as when you want to integrate it into an editor:

```sh
composer create-project "php-integrator/core" "php-integrator-core" --prefer-dist --no-dev
```

### Development
If you want to make the core part of your (existing) project and use the classes contained inside it for your own purposes:

```sh
composer require "php-integrator/core"
```

Note that the core was designed primarily as an application and not as a library. However, it is still very much possible to instantiate the classes you need yourself.

You may also be interested in [other libraries that are part of the php-integrator suite](https://gitlab.com/php-integrator). In the future, more code may be split from the core into proper, separate libraries.

## Contributing
See [our contribution guide](https://gitlab.com/php-integrator/core/blob/development/CONTRIBUTING.md).

![GPLv3 Logo](https://gitlab.com/php-integrator/core/raw/793c93b0f69a5f4ba183f1dfff79f0c68d9bd010/resources/images/gpl_v3.png)
