<?php

use Kipchak\Core\Components\Middlewares\AuthJwks;
use Kipchak\Core\Components\Middlewares\AuthKey;
use Kipchak\Driver\Config\Driver as Config;
use Slim\App;

/**
 * @var App $app
 */

$container = $app->getContainer();
if (!empty(Config::get('kipchak.auth'))) {
    $auth = Config::get('kipchak.auth');
    if ($auth['jwks']['enabled']) {
        // Enable JWKS globally
        $app->add(new AuthJwks($container));
    }

    if ($auth['key']['enabled']) {
        // Enable Key based auth globally
        $app->add(new AuthKey($container));

    }
}
