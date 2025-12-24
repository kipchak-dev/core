<?php

namespace Kipchak\Core\Interfaces;

use Psr\Container\ContainerInterface;
use Slim\App;

interface Middleware
{
    public static function initialise(App $app);

}