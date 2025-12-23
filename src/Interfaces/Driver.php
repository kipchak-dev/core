<?php

namespace Mamluk\Kipchak\Interfaces;

use Psr\Container\ContainerInterface;

interface Driver
{
    public function __construct(ContainerInterface $container);
    public function set();
    public function get();


}