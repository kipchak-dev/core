<?php

namespace Kipchak\Core\Interfaces;

use Psr\Container\ContainerInterface;

interface Driver
{
    public static function initialise(ContainerInterface $container, ?string $configFileName = null);
    public static function set();
    public static function get(?string $configKey = null);


}