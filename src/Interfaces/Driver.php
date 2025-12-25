<?php

namespace Kipchak\Core\Interfaces;

use Psr\Container\ContainerInterface;

interface Driver
{
    /**
     * Initialise the driver
     * @var ContainerInterface $container Slim Container
     * @var string|null $configFileName Name of the config file in the config folder of the API without the .php extension
     */
    public static function initialise(ContainerInterface $container, ?string $configFileName = null);
    public static function set();
    public static function get(?string $configKey = null);


}