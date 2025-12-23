<?php

use Kipchak\Core\Components\Exceptions\Handlers\DefaultHandler;
use Kipchak\Driver\Config\Driver as Config;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * @var ContainerInterface $container
 * @var App $app
 */

$container = $app->getContainer();
$api = Config::get('kipchak.api');
$logExceptions = isset($api['logExceptions']) && (bool) $api['logExceptions'];
$logExceptionDetails = isset($api['logExceptionDetails']) && (bool) $api['logExceptionDetails'];

// Add Application middleware
$errorMiddleware = $app->addErrorMiddleware($logExceptions, $logExceptions, $logExceptionDetails);

// Configure error middleware
$errorMiddleware->setDefaultErrorHandler(
    new DefaultHandler(
        $app->getCallableResolver(),
        $app->getResponseFactory()
    )
);
