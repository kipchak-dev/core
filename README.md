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

Kipchak is an API Development Kit (ADK) built on [Slim Framework](https://www.slimframework.com), designed for rapid API development. Written in PHP, it combines years of production experience handling thousands of concurrent requests.

## Quick Start

To see Kipchak in action, visit our [starter repository](https://1x.ax/mamluk/kipchak/starter).

## Features in the Community Edition

| Category          | Components                                                                                                 |
|-------------------|------------------------------------------------------------------------------------------------------------|
| Core Features     | • Easy config management<br>• Consistent bootstrapping<br>• Dependency injection<br>• Middleware injection |
| Caching           | • Symfony Cache<br>• Memcached support<br>• File System Caching                                            |
| Database          | • Doctrine DBAL and ORM<br>• CouchDB Client                                                                |
| Authentication    | • OAuth 2 with JWKS<br>• Key-based authentication                                                          |
| Development Tools | • Object Mapping (Valinor)<br>• Pre-configured testing                                                     |
| Infrastructure    | • Base Dockerfile<br>• NGINX Unit support                                                                  |
| Documentation     | • OpenAPI using Zircotte Swagger PHP                                                                       |

## Enterprise Features (Paid)

* **Valkey** support for caching
* **JWT** authentication / authorisation with a public key
* **Binary only or security verified** containers
* **RBAC Management**
* **Personal Access Token Management**
* **Rate limting** by IP address / user / API key
* **OAuth 2 Client** with simplified token management for upstream APIs

To learn more about Kipchak Enterprise Edition, please reach out via https://mamluk.net/contact.

## Additional Open Source Components you can use with Kipchak
| Category          | Components                                                            |
|-------------------|-----------------------------------------------------------------------|
| Message Queues    | Tavashan by Mamluk (https://1x.ax/mamluk/tavshan - a Rabbit MQ Client |

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
| Cache/ORM      | Symfony               |
| HTTP Client    | Laravel               |
| Logging        | Monolog               |
| Object Mapping | Team CuyZ (Valinor)   |
| Open API Docs  | Zircotte              |
| Couch DB       | Mamluk CouchDB Client |

For complete dependencies, see our [composer.json](https://1x.ax/mamluk/kipchak/core/~files/master/composer.json).

## About

- **Company**: [Mamluk](https://mamluk.net)
- **Name Origin**: Named after the Turkic Kipchak family which founded the Mamluk Sultanate of Cairo in 1250
- **Documentation**: Available at [Kipchak Starter](https://1x.ax/mamluk/kipchak/starter)