<?php

namespace Kipchak\Core\Components\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
//use Slim\App;
use Kipchak\Core\Components\App;
use Slim\Factory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;

class AppFactory extends Factory\AppFactory
{
    /**
     * @template TContainerInterface of (ContainerInterface|null)
     * @param TContainerInterface $container
     * @return (TContainerInterface is ContainerInterface ? App<TContainerInterface> : App<ContainerInterface|null>)
     */
    public static function create(
        ?ResponseFactoryInterface $responseFactory = null,
        ?ContainerInterface $container = null,
        ?CallableResolverInterface $callableResolver = null,
        ?RouteCollectorInterface $routeCollector = null,
        ?RouteResolverInterface $routeResolver = null,
        ?MiddlewareDispatcherInterface $middlewareDispatcher = null
    ): App {
        static::$responseFactory = $responseFactory ?? static::$responseFactory;
        return new App(
            self::determineResponseFactory(),
            $container ?? static::$container,
            $callableResolver ?? static::$callableResolver,
            $routeCollector ?? static::$routeCollector,
            $routeResolver ?? static::$routeResolver,
            $middlewareDispatcher ?? static::$middlewareDispatcher
        );
    }

}