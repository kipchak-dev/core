<?php

namespace Kipchak\Core;

use Kipchak\Core\Components\Factory\AppFactory;
use Kipchak\Core\Components\App;
use DI\Container;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Api
{
    public static function boot(): App
    {
        $container = new Container();
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        $app->addRoutingMiddleware();
        self::loadDrivers($app);
        self::loadMiddlewares($app);
        self::loadRoutes($app);

        return $app;
    }

    public static function bootCli(): App
    {
        // Create DI Container and App
        $container = new Container();
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        self::loadDrivers($app);

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
            if (str_contains($driver, '.php')) {
                require_once(realpath($driver));
            }
        }
    }

    public static function loadMiddlewares(App $app): void
    {
        $container = $app->getContainer();

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath(__DIR__ . '/../../../../middlewares')));
        $middlewares = array_keys(array_filter(iterator_to_array($iterator), function ($file) {
            return $file->isFile();
        }));

        foreach ($middlewares as $middleware) {
            if (str_contains($middleware, '.php')) {
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
            if (str_contains($route, '.php')) {
                require_once(realpath($route));
            }
        }
        /***/
    }
}