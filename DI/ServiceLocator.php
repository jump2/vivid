<?php

namespace Vivid\DI;

use Vivid\Base\Object;
use Vivid\DI\Container;

class ServiceLocator extends Object
{
    private $_singletons = [];

    private $_definitions = [];

    private static $container;

    public function init()
    {
        parent::init();
        self::$container = Container::getInstance();
    }

    public function get($name)
    {
        if(isset($this->_singletons[$name])) {
            return $this->_singletons[$name];
        }

        return self::$container->get($name);
    }

    public function bind($name, $value, $share = false)
    {
        self::$container->set($name, $value, $share);
    }

    public function singleton($name, $value)
    {
        $this->bind($name, $value, true);
    }
}