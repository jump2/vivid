<?php

namespace Vivid\Base\Exception;

class UnknownPropertyException extends \Exception
{
    public function getName()
    {
        return 'Unknown Property';
    }
}