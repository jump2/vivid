<?php

namespace Vivid\DI;

use Vivid\Base\Object;

use Vivid\Base\Exception\InvalidConfigException;
use Vivid\DI\Exception\NotInstantiableException;

class Container extends Object
{
    private static $_instance;

    private $_singletons = [];

    private $_definitions = [];

    private $_params = [];

    private $_reflections = [];

    private $_dependencies = [];

    public static function getInstance()
    {
        if(!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Registers a class definition with this container.
     *
     * For example,
     *
     * ```php
     * // register a class name as is. This can be skipped.
     * $container->bind('vivid\db\Connection');
     *
     * // register an interface
     * // When a class depends on the interface, the corresponding class
     * // will be instantiated as the dependent object
     * $container->bind('vivid\mail\MailInterface', 'vivid\swiftmailer\Mailer');
     *
     * // register an alias name. You can use $container->get('foo')
     * // to create an instance of Connection
     * $container->bind('foo', 'vivid\db\Connection');
     *
     * // register a class with configuration. The configuration
     * // will be applied when the class is instantiated by get()
     * $container->bind('vivid\db\Connection', [
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register an alias name with class configuration
     * // In this case, a "class" element is required to specify the class
     * $container->bind('db', [
     *     'class' => 'vivid\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register a PHP callable
     * // The callable will be executed when $container->get('db') is called
     * $container->bind('db', function ($container, $params, $config) {
     *     return new \vivid\db\Connection($config);
     * });
     * ```
     *
     * @param string $class class name, interface name or alias name
     * @param array $definition
     * @param array $params array $params the list of constructor parameters. The parameters will be passed to the class
     * constructor when [[get()]] is called.
     * @param bool $share singleton class when it's true
     */
    public function bind($class, $definition = [], $params = [], $share = false)
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        $this->_params[$class] = $params;
        if($share) {
            $this->_singletons[$class] = null;
        } else {
            unset($this->_singletons[$class]);
        }
        return $this;
    }

    public function singleton($class, $definition = [], $params = [])
    {
        return $this->bind($class, $definition, $params, true);
    }

    protected function normalizeDefinition($class, $definition)
    {
        if(empty($definition)) {
            return ['class' => $class];
        } elseif(is_string($definition)) {
            return ['class' => $definition];
        } elseif(is_callable($definition, true) || is_object($definition)) {
            return $definition;
        } elseif(is_array($definition) && !isset($definition['class'])) {
            $definition['class'] = $class;
            return $definition;
        } else {
            throw new InvalidConfigException("Unsupported definition type for \"$class\": " . gettype($definition));
        }
    }

    public function build($class, $params, $config)
    {
        list($reflection, $dependencies) = $this->getDependencies($class, $params);

        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }

        if (!empty($dependencies) && $reflection->implementsInterface('Vivid\Base\Configurable')) {
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;
            return $reflection->newInstanceArgs($dependencies);
        } else {
            $object = $reflection->newInstanceArgs($dependencies);
            foreach ($config as $name => $value) {
                $object->$name = $value;
            }
            return $object;
        }
    }

    public function get($class, $params = [], $config = [])
    {
        if(isset($this->_singletons[$class])) {
            return $this->_singletons[$class];
        } elseif (!isset($this->_definitions[$class])) {
            return $this->build($class, $params, $config);
        }

        $definition = $this->_definitions[$class];

        if(is_callable($definition, true)) {
            $params = $this->mergeParams($class, $params);
            $object = call_user_func($definition, $this, $params, $config);
        } elseif(is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } elseif(is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);

            $config = array_merge($definition, $config);
            $params = $this->mergeParams($class, $params);

            if ($concrete === $class) {
                $object = $this->build($class, $params, $config);
            } else {
                $object = $this->get($concrete, $params, $config);
            }
        } else {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
        }

        if (array_key_exists($class, $this->_singletons)) {
            $this->_singletons[$class] = $object;
        }

        return $object;
    }

    protected function getDependencies($class, $params)
    {
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        foreach($params as $index => $param) {
            $dependencies[$index] = $param;
        }
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $i => $param) {
                if($i <= $index) continue;
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    $c = $param->getClass();
                    if($c === null) {
                        throw new InvalidConfigException("Missing required parameter \"$param->getName()\" when instantiating \"$class\".");
                    } else {
                        $dependencies[] = $this->get($c->getName());
                    }
                }
            }
        }

        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    protected function mergeParams($class, $params)
    {
        if (empty($this->_params[$class])) {
            return $params;
        } elseif (empty($params)) {
            return $this->_params[$class];
        } else {
            $ps = $this->_params[$class];
            foreach ($params as $index => $value) {
                $ps[$index] = $value;
            }
            return $ps;
        }
    }
}