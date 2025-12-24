<?php

namespace Kipchak\Core\Interfaces;

use Psr\Container\ContainerInterface;

interface Middleware
{
    public static function initialise(ContainerInterface $container);

}