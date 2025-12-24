<?php

namespace Kipchak\Core\Components\Controllers;

use Kipchak\Driver\Logger\Driver as Logger;
use Monolog\Logger as Log;
use Psr\Container\ContainerInterface;

class Base
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Log
     */
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = Logger::get();
    }
}