<?php

namespace Kipchak\Core;

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


