<?php

namespace Vivid\Base;

use Vivid\DI\Container;

class Application extends Container
{
    const VERSION = '0.0.1';

    public function version()
    {
        return static::VERSION;
    }

    public function setComponents($components, $share = false)
    {
        foreach ($components as $id => $component) {
            $this->bind($id, $component, [], $share);
        }
    }

    public function setShareComponents($components)
    {
        $this->setComponents($components, true);
    }
}