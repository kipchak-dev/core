<?php

namespace Mamluk\Kipchak\Interfaces;

use Psr\Container\ContainerInterface;

interface Driver
{
    public function initialise(ContainerInterface $container);
    public static function set();
    public static function get();


}