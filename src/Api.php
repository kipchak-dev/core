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
        require_once(realpath(__DIR__ . '/../drivers/logger.php'));
        /** Load all the dependency files in the /drivers folder of this project **/
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath(__DIR__ . '/../drivers')));
        $dependencies = array_keys(array_filter(iterator_to_array($iterator), function($file) {
            return $file->isFile();
        }));

        foreach ($dependencies as $dependency) {
            if (strpos($dependency, '.php') !== false) {
                require_once(realpath($dependency));
            }
        }

        /** Load all driver.php files from vendor/kipchak/driver-* subdirectories **/
        $vendorPath = realpath(__DIR__ . '/../../../../vendor/kipchak');
        if ($vendorPath && is_dir($vendorPath)) {
            $driverDirs = glob($vendorPath . '/driver-*', GLOB_ONLYDIR);
            foreach ($driverDirs as $driverDir) {
                $driverFile = $driverDir . '/driver.php';
                if (file_exists($driverFile)) {
                    require_once($driverFile);
                }
            }
        }
        /***/
    }

    public static function loadMiddlewares(App $app): void
    {
        $container = $app->getContainer();
        /** Load all the middleware files in the /routes folder of this project **/
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath(__DIR__ . '/../middlewares')));
        $middlewares = array_keys(array_filter(iterator_to_array($iterator), function($file) {
            return $file->isFile();
        }));

        foreach ($middlewares as $middleware) {
            if (strpos($middleware, '.php') !== false) {
                require_once(realpath($middleware));
            }
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath(__DIR__ . '/../../../../middlewares')));
        $middlewares = array_keys(array_filter(iterator_to_array($iterator), function($file) {
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
        $routes = array_keys(array_filter(iterator_to_array($iterator), function($file) {
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