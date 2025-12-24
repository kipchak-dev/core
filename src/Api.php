<?php

namespace Kipchak\Core;

use Slim\Factory\AppFactory;
use Slim\App;
use DI\Container;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Api
{
    public static function boot(): App
    {
        // Create DI Container and App
        $container = new Container();
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        $app->addRoutingMiddleware();
        $container = $app->getContainer();

        self::loadDrivers($app, $container);
        self::loadMiddlewares($app, $container);
        self::loadRoutes($app, $container);

        return $app;
    }

    public static function bootCli(): App
    {
        // Create DI Container and App
        $container = new Container();
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        $app->addRoutingMiddleware();
        $container = $app->getContainer();

        self::loadDrivers($app, $container);

        return $app;
    }

    public static function loadDrivers(App $app): void
    {
        $container = $app->getContainer();
        // Load static functions
        require_once(realpath(__DIR__ . '/Functions.php'));

        /** Load drivers inititalised in the project */
        $projectDriversPath = realpath(__DIR__ . '/../../../../drivers');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectDriversPath));
        $drivers = array_keys(array_filter(iterator_to_array($iterator), function ($file) {
            return $file->isFile();
        }));

        foreach ($drivers as $driver) {
            if (strpos($driver, '.php') !== false) {
                require_once(realpath($driver));
            }
        }
    }

    public static function loadMiddlewares(App $app): void
    {
        $container = $app->getContainer();
        /** Load all the middleware files in the /routes folder of this project **/
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath(__DIR__ . '/../middlewares')));
        $middlewares = array_keys(array_filter(iterator_to_array($iterator), function ($file) {
            return $file->isFile();
        }));

        foreach ($middlewares as $middleware) {
            if (strpos($middleware, '.php') !== false) {
                require_once(realpath($middleware));
            }
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath(__DIR__ . '/../../../../middlewares')));
        $middlewares = array_keys(array_filter(iterator_to_array($iterator), function ($file) {
            return $file->isFile();
        }));

        foreach ($middlewares as $middleware) {
            if (strpos($middleware, '.php') !== false) {
                require_once(realpath($middleware));
            }
        }
        /***/
    }

    public static function loadRoutes(App $app): void
    {
        $container = $app->getContainer();
        /** Load all the routes files in the /routes folder of this project **/
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath(__DIR__ . '/../../../../routes')));
        $routes = array_keys(array_filter(iterator_to_array($iterator), function ($file) {
            return $file->isFile();
        }));

        foreach ($routes as $route) {
            if (strpos($route, '.php') !== false) {
                require_once(realpath($route));
            }
        }
        /***/
    }
}