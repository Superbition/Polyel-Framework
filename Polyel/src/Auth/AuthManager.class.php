<?php

namespace Polyel\Auth;

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
}