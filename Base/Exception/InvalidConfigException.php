<?php

namespace Vivid\Base\Exception;

class InvalidConfigException extends \Exception
{
    public function getName()
    {
        return 'Invalid Configuration';
    }
}