<?php

namespace Polyel\Auth;

use RuntimeException;
use Polyel\Auth\SourceDrivers\Database;
use Polyel\Auth\Protectors\TokenProtector;
use Polyel\Auth\Protectors\SessionProtector;

class AuthManager
{
    private $HttpKernel;

    private $protectors;

    private $users;

    public function __construct(Database $users, SessionProtector $sessionProtector, TokenProtector $tokenProtector)
    {
        $this->users = $users;

        $this->protectors['session'] = $sessionProtector;
        $this->protectors['token'] = $tokenProtector;
    }

    public function initialise($HttpKernel)
    {
        $this->HttpKernel = $HttpKernel;
    }

    public function setSource($source)
    {
        $this->users->setTable($source);
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