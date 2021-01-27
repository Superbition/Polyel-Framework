<?php

namespace Polyel\System\Exceptions;

use Exception;

class ThirdPartyUnknownFileException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}