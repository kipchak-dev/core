<?php

namespace Kipchak\Core\Interfaces;

use Psr\Container\ContainerInterface;

interface Driver
{
    public static function initialise(ContainerInterface $container);
    public static function set();
    public static function get(?string $configKey = null);


}