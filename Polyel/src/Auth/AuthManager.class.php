<?php

namespace Polyel\Auth;

use RuntimeException;
use Polyel\Auth\Protectors\TokenProtector;
use Polyel\Auth\Protectors\SessionProtector;

class AuthManager
{
    private $HttpKernel;

    private $protectors;

    public function __construct(SessionProtector $sessionProtector, TokenProtector $tokenProtector)
    {
        $this->protectors['session'] = $sessionProtector;
        $this->protectors['token'] = $tokenProtector;
    }

    public function initialise($HttpKernel)
    {
        $this->HttpKernel = $HttpKernel;
    }

    public function protector($protector)
    {
        if(array_key_exists($protector, $this->protectors))
        {
            return $this->protectors[$protector];
        }

        throw new RuntimeException('Invalid protector requested: ' . $protector);
    }
}