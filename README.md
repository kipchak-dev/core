[version-badge]: https://poser.pugx.org/mamluk/kipchak/v
[downloads-badge]: https://poser.pugx.org/mamluk/kipchak/downloads
[unstable-badge]: https://poser.pugx.org/mamluk/kipchak/v/unstable
[license-badge]: https://poser.pugx.org/mamluk/kipchak/license
[php-badge]: https://poser.pugx.org/mamluk/kipchak/require/php
[packagist-url]: https://packagist.org/packages/mamluk/kipchak
[slim-url]: https://www.slimframework.com/

![Version][version-badge]
![Downloads][downloads-badge]
![Unstable][unstable-badge]
![License][license-badge]
![PHP Version][php-badge]

<img src="https://1x.ax/mamluk/kipchak/core/~raw/master/.mamluk/logo.svg" alt="Kipchak by Mamluk" title="Kipchak by Mamluk - an API Toolkit" />

# Kipchak by Mamluk

## Overview

Kipchak is an API Development Kit (ADK) built on [Slim Framework](https://www.slimframework.com), designed for rapid API development. 
Written in PHP, it combines years of production experience handling thousands of concurrent requests.

Kipchak is built to be extremely lightweight and easy to use. Its core components just include:

* An HTTP request and Response handler
* A base controller
* ّInterfaces to write drivers (dependencies you inject into the service container) and middleware
* Zircotte Swagger PHP for generating OpenAPI documentation
* Cuyz Valinor for object mapping

## Quick Start

To see Kipchak in action, visit our [starter repository](https://1x.ax/mamluk/kipchak/starter).

## Features

### Drivers

Drivers are the components that you can inject into the service container to extend Kipchak's functionality. They can be written directly in your 
Kipchak project or can be installed as separate packages. The following packages are available:

* Memached
* Filecache (for caching to disk)
* CouchDB (based on Mamluk's CouchDB Client)
* Doctrine ORM (from Symfony)
* Logger (Monolog)
* HTTP Client (based on Laravel)
* Config (for managing configuration files)
* Rabbit MQ (based on Tavshan by Mamluk)
* Object Storage

For a complete list, please visit https://1x.ax/mamluk/kipchak/drivers.

### Middlewares

PSR 15 middlewares can be used to extend Kipchak's functionality. To better understand middlewares, please visit https://www.php-fig.org/psr/psr-15/.

The following middlewares are available:

* JWKS based Authentication
* Key-based Authentication
* WAF that supports:
  * Blacklisting
  * Whitelisting
  * Rate limiting

For a complete list, please visit https://1x.ax/mamluk/kipchak/middlewares. |

## Enterprise Features (Paid)

* **Valkey** support for caching
* **JWT** authentication / authorisation with a public key
* **Binary only or security verified** containers
* **RBAC Management**
* **Personal Access Token Management**
* **OAuth 2 Client** with simplified token management for upstream APIs

To learn more about Kipchak Enterprise Edition, please reach out via https://mamluk.net/contact.

## Key Differences between versions 1.x and versions 2.x
* Package name has changed from mamlu/kipchak to kipchak/core
* Namespace has changed from Mamluk\Kipchak to Kipchak
* The project has been restructured to be more modular and has thus introduced:
  * Drivers
  * Middlewares
* Interfaces have been added to make it easier to write drivers and middlewares
* The starter repository has been updated to use the new structure

## Key Differences between versions 0.x and versions 1.x

* Session management has not been used even once in a 0.x implementation as it's always best to keep APIs stateless so it has been removed
* Zircote Swagger PHP has been added for generating OpenAPI documentation from PHP Attributes
* Doctrine has now been upgraded to the stable version 3.5+. *This may result in breaking changes*, especially for migrations and if you were using / extending some core Doctrine capabilities without specifying return types.

## Technical Stack

- **Framework**: Built on [Slim Framework](https://www.slimframework.com)
- **Memory Footprint**: Lightweight compared to Symfony/Laravel
- **Integration**: Compatible with Symfony/Laravel libraries
- **PHP 8.3+**

## Credits

| Component      | Provider              |
|----------------|-----------------------|
| Framework      | Slim Framework        |
| ORM            | Symfony Doctrine      |
| HTTP Client    | Laravel               |
| Logging        | Monolog               |
| Object Mapping | Team CuyZ (Valinor)   |
| Open API Docs  | Zircotte              |
| Couch DB       | Mamluk CouchDB Client |
| Rabbit MQ      | Tavshan by Mamluk     |
| Cache          | Memcached / Symfony   |


For complete dependencies, see our [composer.json](https://1x.ax/mamluk/kipchak/core/~files/master/composer.json) and the starter project's [composer.json](https://1x.ax/mamluk/kipchak/starter/~files/master/composer.json).

## About

- **Company**: [Mamluk](https://mamluk.net)
- **Name Origin**: Named after the Turkic Kipchak family which founded the Mamluk Sultanate of Cairo in 1250
- **Documentation**: Available at [Kipchak Starter](https://1x.ax/mamluk/kipchak/starter)