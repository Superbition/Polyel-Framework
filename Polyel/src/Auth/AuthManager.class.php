<?php

namespace Polyel\Auth;

use Polyel\Auth\SourceDrivers\Database;

class AuthManager
{
    private $HttpKernel;

    private $protectors;

    private $users;

    public function __construct(Database $users)
    {
        $this->users = $users;
    }

    public function initialise($HttpKernel)
    {
        $this->HttpKernel = $HttpKernel;
    }
}