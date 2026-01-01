<?php

namespace Kipchak\Core;

use Kipchak\Core\Components\Http;
use Psr\Http\Message\ResponseInterface;
use Kipchak\Core\Components\Psr7\Response;
use const Grpc\STATUS_OK;

/**
 * @param string $file name of config file in the config folder of the Kipchak API
 * @return array
 */
function config(string $file): array
{
    $configDir = realpath(__DIR__ . '/../../../../config/');
    $configFile = $configDir . '/' . $file . '.php';
    if (file_exists($configFile)) {
        $config = include_once($configFile);

        return $config;
    }

    return [];
}

/**
 * @param string $name Name of the environment variable to return
 * @param string $default This will be returned if the environment variable does not exist
 * @return string
 */
function env(string $name, string $default): string
{
    if (getenv($name)) {
        return getenv($name);
    }

    return $default;
}

/**
 * There is no option to dump data on the screen using var_dump() or print_r() in FrankenPHP's worker mode.
 * This function will dump the data to the screen and return a 200 OK response.
 * BUT, it can only be used inside a controller or middleware. It CANNOT be used earlier in the bootstrap process,
 * like in the middlewares/middlewares.php file or the drivers/drivers.php file.
 * @param mixed $data
 */
function dump(mixed $data): ResponseInterface
{
    return Http\Response::raw(new Response(), $data, 200);
}


