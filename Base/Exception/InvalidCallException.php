<?php

namespace Vivid\Base\Exception;

class InvalidCallException extends \BadMethodCallException
{
    function getName()
    {
        return 'Invalid Call';
    }
}