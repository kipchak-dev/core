<?php

namespace Kipchak\Core\Interfaces;

use Kipchak\Core\Components\App;

interface Middleware
{
    public static function initialise(App $app);

}