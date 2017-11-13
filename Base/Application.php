<?php

namespace Vivid\Base;

use Vivid\DI\ServiceLocator;

class Application extends ServiceLocator
{
    const VERSION = '0.0.1';

    public function version()
    {
        return static::VERSION;
    }
}